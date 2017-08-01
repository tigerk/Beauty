<?php

namespace Beauty\Lib;

/**
 * 哈希环的实现
 * 默认使用一致性哈希算法，hash采用murmur
 * User: tigerkim
 */
class HashRing
{
    // pecl memcache  余数分布法
    const DISTRIBUTION_STANDARD = 0;

    // pecl memcache  一致性hash算法
    const DISTRIBUTION_CONSISTENT = 1;

    // pecl memcached 余数分布法
    const DISTRIBUTION_MODULA = 2;

    // pecl memcached 一致性hash算法
    const DISTRIBUTION_KETAMA = 3;

    // 兼容模式, 可以与 python 等其他语言客户端使用相同分布算法
    const DISTRIBUTION_COMPATIBLE = 4;

    // 可用 hash算法
    const HASH_DEFAULT  = 0;
    const HASH_MD5      = 1;
    const HASH_CRC      = 2;
    const HASH_FNV1_64  = 3;
    const HASH_FNV1A_64 = 4;
    const HASH_FNV1_32  = 5;
    const HASH_FNV1A_32 = 6;
    const HASH_HSIEH    = 7;
    const HASH_MURMUR   = 8;

    /**
     * 分布算法
     * @var int
     */
    private static $distribution;

    /**
     * hash算法
     * @var int
     */
    private static $hash;

    /**
     * 虚拟节点数 (在使用 DISTRIBUTION_CONSISTENT 算法时使用)
     * @var int
     */
    private static $consistentPoint = 160;

    /**
     * hash环节点数, 2 的幂数 (在使用 DISTRIBUTION_CONSISTENT 算法时使用)
     * @var int
     */
    private static $consistentBuckets = 1024;

    /**
     * php 支持的 hash 函数 (用于判断是否支持 fnv 算法, php5.4 以后才内置了 fnv 算法)
     * @var null
     */
    private static $hashSupport = null;

    /**
     * server(cache节点) => weight(权重)
     * @var array { server => weight, ... }
     */
    private $servers = [];

    /**
     * cache 节点计数器
     * @var int
     */
    private $serverCount = 0;

    /**
     * 虚拟节点计数器
     * @var int
     */
    private $bucketCount = 0;

    /**
     * key(序号) => server(cache节点)
     * @var array { key => server, ... }
     */
    private $buckets = [];

    /**
     * servers 处理结果 (在使用 DISTRIBUTION_KETAMA 算法时使用)
     * @var array
     */
    private $ketama = [];

    /**
     * 虚拟节点是否已经处理
     * @var bool
     */
    private $bucketPopulated = false;

    /**
     * 故障节点
     * @var array { server => 1, ... }
     */
    private $failServers = [];

    /**
     * 当前指定的 key
     * @var null|string
     */
    private $currentKey = null;

    /**
     * 当前分配的 server
     * @var null|string
     */
    private $currentServer = null;

    /**
     * next() 尝试次数
     * @var int
     */
    private $currentTry = 0;

    /**
     * 实例化
     * @param int $distribution 分布算法
     * @param int $hash hash算法 (当分布算法为 DISTRIBUTION_COMPATIBLE $hash 参数将被忽略)
     */
    public function __construct($distribution = self::DISTRIBUTION_CONSISTENT, $hash = self::HASH_MURMUR)
    {
        $this->setOption($distribution, $hash);
    }

    /**
     * 系统内置支持的 hash 算法
     * @return array
     */
    protected static function supportHash()
    {
        if (self::$hashSupport === null) {
            self::$hashSupport = hash_algos();
        }

        return self::$hashSupport;
    }

    /**
     * 向右偏移 (php 32位溢出后 向右偏移不正确)
     * @param $int
     * @param $shift
     * @return int
     */
    protected static function shiftRight($int, $shift)
    {
        if (($int = (int)$int) >= 0) {
            return $int >> $shift;
        }

        return (($int & 0x7FFFFFFF) >> $shift) | (0x40000000 >> ($shift - 1));
    }

    /**
     * 支持 hash: one-at-a-time
     * @param $str
     * @return float
     */
    public static function oneAtaTime($str)
    {
        $hash = 0;
        foreach (str_split($str) as $byte) {
            $hash += ord($byte);
            $hash += $hash << 10;
            $hash ^= self::shiftRight($hash, 6);
        }
        $hash += ($hash << 3);
        $hash ^= self::shiftRight($hash, 11);
        $hash += ($hash << 15);

        return sprintf("%u", $hash);
    }

    /**
     * 支持 hash: crc32
     * @param $str
     * @return float
     */
    public static function crc32($str)
    {
        $hash = crc32($str);
        if (self::$distribution === self::DISTRIBUTION_CONSISTENT) {
            // 兼容 pecl memcache consistent crc
            return sprintf('%u', $hash);
        }

        return (sprintf('%d', $hash) >> 16) & 0x7fff;
    }

    /**
     * 支持 hash: md5
     * @param $str
     * @return float
     */
    public static function md5($str)
    {
        $hash   = md5($str);
        $hashes = str_split(substr($hash, 0, 8), 2);
        $hash   = $hashes[3] . $hashes[2] . $hashes[1] . $hashes[0];

        return (float)base_convert($hash, 16, 10);
    }

    /**
     * 支持 hash: fnv132
     * @param $str
     * @return float
     */
    public static function fnv132($str)
    {
        // fnv 函数在 php5.4 之后才默认支持
        if (in_array('fnv132', self::supportHash())) {
            return hexdec(hash('fnv132', $str));
        }
        $hash = 0x811c9dc5;
        foreach (str_split($str) as $byte) {
            $hash += ($hash << 1) + ($hash << 4) + ($hash << 7) + ($hash << 8) + ($hash << 24);
            $hash ^= ord($byte);
        }

        return sprintf('%u', $hash & 0xffffffff);
    }

    /**
     * 支持 hash: fnv1a32
     * @param $str
     * @return float
     */
    public static function fnv1a32($str)
    {
        if (in_array('fnv1a32', self::supportHash())) {
            $hash = hexdec(hash('fnv1a32', $str));
            if (self::$distribution === self::DISTRIBUTION_STANDARD) {
                // 兼容 pecl memcache standard fnv
                return ($hash >> 16) & 0x7fff;
            }

            return $hash;
        }
        $hash = 0x811c9dc5;
        foreach (str_split($str) as $byte) {
            $hash ^= ord($byte);
            $hash += ($hash << 1) + ($hash << 4) + ($hash << 7) + ($hash << 8) + ($hash << 24);
        }
        $hash = $hash & 0xffffffff;
        if (self::$distribution === self::DISTRIBUTION_STANDARD) {
            // 兼容 pecl memcache standard fnv
            return ($hash >> 16) & 0x7fff;
        }

        return sprintf('%u', $hash);
    }

    /**
     * 支持 hash: fnv164 (仅取低八位,保留32bit)
     * @param $str
     * @return float
     */
    public static function fnv164($str)
    {
        if (in_array('fnv164', self::supportHash())) {
            return (float)base_convert(substr(hash('fnv164', $str), -8), 16, 10);
        }
        $hash = 0x84222325;
        foreach (str_split($str) as $byte) {
            $hash += ($hash << 1) + ($hash << 4) + ($hash << 5) + ($hash << 7) + ($hash << 8);
            $hash ^= ord($byte);
        }

        return sprintf('%u', $hash);
    }

    /**
     * 支持 hash: fnv1a64 (仅取低八位,保留32bit)
     * @param $str
     * @return float
     */
    public static function fnv1a64($str)
    {
        if (in_array('fnv1a64', self::supportHash())) {
            return (float)base_convert(substr(hash('fnv1a64', $str), -8), 16, 10);
        }
        $hash = 0x84222325;
        foreach (str_split($str) as $byte) {
            $hash ^= ord($byte);
            $hash += ($hash << 1) + ($hash << 4) + ($hash << 5) + ($hash << 7) + ($hash << 8);
        }

        return sprintf('%u', $hash);
    }

    /**
     * 支持 hash: hsieh
     * @param $str
     * @return float
     */
    public static function hsieh($str)
    {
        $length = strlen($str);
        $rem    = $length & 3;
        $length >>= 2;
        $hash = 0;
        for (; $length > 0; $length--) {
            $hash += self::get16bits($str);
            $tmp  = (self::get16bits(substr($str, 2)) << 11) ^ $hash;
            $hash = ($hash << 16) ^ $tmp;
            $str  = substr($str, 4);
            $hash += self::shiftRight($hash, 11);
        }
        switch ($rem) {
            case 3:
                $hash += self::get16bits($str);
                $hash ^= $hash << 16;
                $hash ^= ord($str[2]) << 18;
                $hash += self::shiftRight($hash, 11);
                break;
            case 2:
                $hash += self::get16bits($str);
                $hash ^= $hash << 11;
                $hash += self::shiftRight($hash, 17);
                break;
            case 1:
                $hash += ord($str);
                $hash ^= $hash << 10;
                $hash += self::shiftRight($hash, 1);
                break;
            default:
                break;
        }
        $hash ^= $hash << 3;
        $hash += self::shiftRight($hash, 5);
        $hash ^= $hash << 4;
        $hash += self::shiftRight($hash, 17);
        $hash ^= $hash << 25;
        $hash += self::shiftRight($hash, 6);

        return sprintf('%u', $hash);
    }

    /**
     * 获得字符串 16bit 的数据 (用在 hsieh hash 算法中)
     * @param $str
     * @return int
     */
    protected static function get16bits($str)
    {
        return ord($str[0]) + (isset($str[1]) ? (ord($str[1]) << 8) : 0);
    }

    /**
     * 支持 hash: murmur
     * @param $str
     * @return float
     */
    public static function murmur($str)
    {
        $prime  = 0x5bd1e995;
        $length = strlen($str);
        $hash   = (0xdeadbeef * $length) ^ $length;
        while ($length >= 4) {
            $key = ord($str[0]) | (ord($str[1]) << 8) | (ord($str[2]) << 16) | (ord($str[3]) << 24);
            $key = self::murmurMul($key, $prime);
            $key ^= self::shiftRight($key, 24);
            $key = self::murmurMul($key, $prime);

            $hash = self::murmurMul($hash, $prime);
            $hash ^= $key;

            $str = substr($str, 4);
            $length -= 4;
        }
        for (; $length > 0; $length--) {
            if ($length > 2) {
                $hash ^= ord($str[2]) << 16;
            } elseif ($length > 1) {
                $hash ^= ord($str[1]) << 8;
            } else {
                $hash ^= ord($str[0]);
                $hash = self::murmurMul($hash, $prime);
            }
        }
        $hash ^= self::shiftRight($hash, 13);
        $hash = self::murmurMul($hash, $prime);
        $hash ^= self::shiftRight($hash, 15);

        return sprintf('%u', $hash);
    }

    /**
     * 获得两个大数字乘积 (用在 murmur hash 算法中)
     * @param $h
     * @param $m
     * @return float
     */
    protected static function murmurMul($h, $m)
    {
        return ((($h & 0xffff) * $m) +
            ((((($h >= 0 ? $h >> 16 : (($h & 0x7fffffff) >> 16) | 0x8000)) * $m) & 0xffff) << 16)
        ) & 0xffffffff;
    }

    /**
     * 获取指定 $key 的 hash
     * @param $key
     * @return int
     */
    public function hash($key)
    {
        switch (self::$hash) {
            case self::HASH_CRC:
                return self::crc32($key);
                break;
            case self::HASH_MD5:
                return self::md5($key);
                break;
            case self::HASH_FNV1_32:
                return self::fnv132($key);
                break;
            case self::HASH_FNV1A_32:
                return self::fnv1a32($key);
                break;
            case self::HASH_FNV1_64:
                return self::fnv164($key);
                break;
            case self::HASH_FNV1A_64:
                return self::fnv1a64($key);
                break;
            case self::HASH_HSIEH:
                return self::hsieh($key);
                break;
            case self::HASH_MURMUR:
                return self::murmur($key);
                break;
        }

        return self::oneAtaTime($key);
    }

    /**
     * 设置分布算法 hash算法
     * @param $distribution
     * @param $hash
     */
    public function setOption($distribution, $hash)
    {
        $trace = null;
        // 检查 是否支持指定分布算法 为简化代码 此处直接写为数字
        $distribution = intval($distribution);
        if ($distribution < 0 || $distribution > 4) {
            $distribution = self::DISTRIBUTION_KETAMA;
        }
        self::$distribution = $distribution;
        // 检查 是否支持指定hash算法  为简化代码 此处直接写为数字
        $hash = intval($hash);
        if ($hash < 0 || $hash > 8) {
            $hash = self::HASH_DEFAULT;
        }
        self::$hash = $hash;
    }

    /**
     * 添加 cache 节点
     *
     * array (
     *   array("host" => "192.168.1.170", "port" => "12111", "weight" => 99),
     *   array("host" => "192.168.1.170", "port" => "12112"),
     * )
     *
     * @param array $nodes
     * @return $this
     */
    public function add($nodes)
    {
        foreach ($nodes as $node) {
            isset($node['weight']) ? $weight = $node['weight'] : $weight = 1;
            if (isset($node['host']) && isset($node['port'])) {
                $server                 = sprintf("%s:%s", $node['host'], $node['port']);
                $this->servers[$server] = $weight;
                $this->serverCount++;
            }
        }

        return $this;
    }

    /**
     * 删除指定 cache 节点
     * @param string $node
     * @return $this
     */
    public function remove($node)
    {
        if (isset($this->servers[$node])) {
            unset($this->servers[$node]);
            $this->serverCount--;
            $this->bucketPopulated = false;
            if (isset($this->failServers[$node])) {
                unset($this->failServers[$node]);
            }
        }

        return $this;
    }

    /**
     * 故障转移指定的 cache 节点
     *    与 remove 不同, 故障转移不会将节点删除, 节点仍参与分配运算, 但当分配到故障节点,会自动跳到下一个
     * @param $node
     * @return $this
     */
    public function failOver($node)
    {
        $this->failServers[$node] = true;

        return $this;
    }

    /**
     * 所有已添加 cache 节点列表
     * @return array { key => [server=>string, weight=>int, fail=>bool] ... }
     */
    public function all()
    {
        $return = [];
        foreach ($this->servers as $server => $weight) {
            $return[] = [
                'server' => $server,
                'weight' => $weight,
                'fail'   => isset($this->failServers[$server]),
            ];
        }

        return $return;
    }

    /**
     * 获取指定 $key 的 cache 节点
     * @param $key
     * @return string|null|array
     */
    public function get($key)
    {
        if ($this->serverCount < 1) {
            return null;
        }
        $this->currentTry = 0;
        $this->currentKey = $key;
        if (self::$distribution === self::DISTRIBUTION_STANDARD || self::$distribution === self::DISTRIBUTION_MODULA) {
            $this->currentServer = $this->standardGet($key);
        } elseif (self::$distribution === self::DISTRIBUTION_KETAMA || self::$distribution === self::DISTRIBUTION_COMPATIBLE) {
            $this->currentServer = $this->ketamaGet($key);
        } else {
            $this->currentServer = $this->consistentGet($key);
        }
        if (isset($this->failServers[$this->currentServer])) {
            return $this->next();
        }

        return $this->currentServer;
    }

    /**
     * 下一个合适的 cache 节点  故障转移使用
     * @return string|null
     */
    protected function next()
    {
        if ($this->currentKey === null || $this->serverCount < 1) {
            return null;
        }
        if (self::$distribution === self::DISTRIBUTION_STANDARD || self::$distribution === self::DISTRIBUTION_MODULA) {
            $server = $this->standardNext();
        } elseif (self::$distribution === self::DISTRIBUTION_KETAMA || self::$distribution === self::DISTRIBUTION_COMPATIBLE) {
            $server = $this->ketamaNext();
        } else {
            $server = $this->consistentNext();
        }
        if (isset($this->failServers[$server]) || ($this->currentServer !== null && $server === $this->currentServer)) {
            return $this->next();
        }
        $this->currentServer = $server;

        return $server;
    }


    /*   Pecl Memcache 一致性hash算法
     * ----------------------------------------------------- */
    protected function consistentGet($key)
    {
        if (!$this->bucketPopulated) {
            $this->consistentPopulate();
        }

        return $this->consistentGetByKey($key);
    }

    protected function consistentNext()
    {
        // 此处获取下一个拼凑方法是可以和余数分布法合并的, 使用不同拼凑方法是为了顺便兼容 2.2.7 以下版本的 perl memcache
        $key = sprintf('%s-%d', $this->currentKey, ++$this->currentTry);

        return $this->consistentGetByKey($key);
    }

    protected function consistentGetByKey($key)
    {
        $point = intval(fmod($this->hash($key), self::$consistentBuckets));

        return isset($this->buckets[$point]) ? $this->buckets[$point] : $this->buckets[0];
    }

    protected function consistentPopulate()
    {
        $this->buckets     = [];
        $this->bucketCount = 0;

        // 将所有 server 按照权重整理为一个数组
        $points = [];
        $sort   = [];
        foreach ($this->servers as $server => $weight) {
            $replicas = $weight * self::$consistentPoint;
            for ($i = 0; $i < $replicas; $i++) {
                $key          = $this->bucketCount + $i;
                $point        = $this->hash(sprintf('%s-%d', $server, $i));
                $points[$key] = [
                    'server' => $server,
                    'point'  => $point,
                ];
                $sort[$key]   = $point;
            }
            $this->bucketCount += $replicas;
        }
        array_multisort($sort, SORT_ASC, $points);

        // 以整理过的 server 数组为数据 分成 $this->consistentBuckets 份均分到一个闭环上
        $step = 0xFFFFFFFF / self::$consistentBuckets;
        $step = (int)$step;
        for ($i = 0; $i < self::$consistentBuckets; $i++) {
            $unit = sprintf('%u', $step * $i);
            $lo   = 0;
            $hi   = $this->bucketCount - 1;

            /**
             * 二分法分布到1024个点
             */
            while (1) {
                if ($unit <= $points[$lo]['point'] || $unit > $points[$hi]['point']) {
                    $this->buckets[$i] = $points[$lo]['server'];
                    break;
                }
                $mid = $lo + ($hi - $lo) / 2;
                $mid = (int)$mid;
                if ($unit <= $points[$mid]['point'] && $unit > ($mid ? $points[$mid - 1]['point'] : 0)) {
                    $this->buckets[$i] = $points[$mid]['server'];
                    break;
                }
                if ($points[$mid]['point'] < $unit) {
                    $lo = (int)($mid + 1);
                } else {
                    $hi = (int)($mid - 1);
                }
            }
        }
        $this->bucketPopulated = true;
    }


    /*   Pecl Memcached 一致性hash算法 / COMPATIBLE 兼容算法
     * ------------------------------------------------------------------------ */
    protected function ketamaGet($key)
    {
        if (!$this->bucketPopulated) {
            $this->ketamaPopulate();
        }

        return $this->ketamaGetByKey($key);
    }

    protected function ketamaNext()
    {
        $key = sprintf('%d%s', ++$this->currentTry, $this->currentKey);

        return $this->ketamaGetByKey($key);
    }

    /**
     * 采用二分查找
     *
     * @param $key
     * @return string
     */
    protected function ketamaGetByKey($key)
    {
        if (self::$distribution === self::DISTRIBUTION_COMPATIBLE) {
            $hash = self::md5($key);
        } else {
            $hash = $this->hash($key);
        }
        $left  = $begin = 0;
        $right = $end = $this->bucketCount;
        while ($left < $right) {
            $middle = $left + floor(($right - $left) / 2);
            if ($this->buckets[$middle]['hash'] < $hash) {
                $left = $middle + 1;
            } else {
                $right = $middle;
            }
        }
        if ($right == $end) {
            $right = $begin;
        }
        if (isset($this->buckets[$right])) {
            $index = $this->buckets[$right]['index'];
        } else {
            $index = 0;
        }
        $server = $this->ketama[$index]['host'];
        if ($this->ketama[$index]['port']) {
            $server .= ':' . $this->ketama[$index]['port'];
        }

        return $server;
    }

    protected function ketamaPopulate()
    {
        $this->ketama = [];
        $totalWeight  = 0;
        $tmpWeight    = false;
        $useWeight    = self::$distribution === self::DISTRIBUTION_COMPATIBLE;
        foreach ($this->servers as $server => $weight) {
            if (strpos($server, ':')) {
                list ($host, $port) = explode(':', $server);
            } else {
                $host = $server;
                $port = false;
            }
            $this->ketama[] = [
                'host'   => $host,
                'port'   => $port,
                'weight' => $weight,
            ];
            $totalWeight += $weight;
            if (!$useWeight) {
                if ($tmpWeight !== false && $tmpWeight != $weight) {
                    $useWeight = true;
                }
                $tmpWeight = $weight;
            }
        }

        $this->buckets     = [];
        $this->bucketCount = 0;
        $pointServer       = $useWeight ? 160 : 100;
        $pointHash         = 1;
        foreach ($this->ketama as $key => $val) {
            if ($useWeight) {
                $pct         = $val['weight'] / $totalWeight;
                $pointServer = floor(($pct * 160 / 4 * $this->serverCount + 0.0000000001)) * 4;
                $pointHash   = 4;
            }
            for ($index = 1; $index <= $pointServer / $pointHash; $index++) {
                if ($val['port'] === false || $val['port'] == 11211) {
                    $sortHost = sprintf("%s-%u", $val['host'], $index - 1);
                } else {
                    $sortHost = sprintf("%s:%u-%u", $val['host'], $val['port'], $index - 1);
                }

                if ($useWeight) {
                    for ($x = 0; $x < $pointHash; $x++) {
                        $hash            = self::ketamaMd5($sortHost, $x);
                        $this->buckets[] = [
                            'index' => $key,
                            'hash'  => $hash,
                        ];
                        $sort[]          = $hash;
                    }
                } else {
                    $hash            = $this->hash($sortHost);
                    $this->buckets[] = [
                        'index' => $key,
                        'hash'  => $hash,
                    ];
                    $sort[]          = $hash;
                }
            }
            $this->bucketCount += $pointServer;
        }
        array_multisort($sort, SORT_ASC, $this->buckets);
        $this->bucketPopulated = true;
    }

    protected static function ketamaMd5($str, $k)
    {
        $hash   = md5($str);
        $hashes = str_split($hash, 2);
        $hash   = ((hexdec($hashes[3 + $k * 4]) & 0xFF) << 24)
            | ((hexdec($hashes[2 + $k * 4]) & 0xFF) << 16)
            | ((hexdec($hashes[1 + $k * 4]) & 0xFF) << 8)
            | (hexdec($hashes[0 + $k * 4]) & 0xFF);

        return sprintf("%u", $hash);
    }


    /*  Pecl Memcache / Pecl Memcached 余数分布算法
     * ----------------------------------------------------- */
    protected function standardGet($key)
    {
        if (!$this->bucketPopulated) {
            $this->standardPopulate();
        }

        return $this->standardGetByKey($key);
    }

    protected function standardNext()
    {
        $key = sprintf('%d%s', ++$this->currentTry, $this->currentKey);

        return $this->standardGetByKey($key);
    }

    protected function standardGetByKey($key)
    {
        $point = intval(fmod($this->hash($key), $this->bucketCount));

        return isset($this->buckets[$point]) ? $this->buckets[$point] : $this->buckets[0];
    }

    protected function standardPopulate()
    {
        $this->buckets     = [];
        $this->bucketCount = 0;
        foreach ($this->servers as $server => $weight) {
            // pecl memcached 在使用余数分布法时  会忽略 weight 的设置
            // 这里为了保持一致 也忽略 weight 设置
            if (self::$distribution === self::DISTRIBUTION_MODULA) {
                $weight = 1;
            }

            for ($i = 0; $i < $weight; $i++) {
                $this->buckets[$this->bucketCount + $i] = $server;
            }
            $this->bucketCount += $weight;
        }
        $this->bucketPopulated = true;
    }
}
