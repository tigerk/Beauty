<?php

/**
 * 目前仅支持memcached和redis两种缓存。
 * 不区分有多个memcached和redis实例
 * 全部采用自己实现的一致性哈希来实现。
 */

return [
    'memcached' => array(
        "prefix" => "",
        "hosts"  => array(
            array(
                'host'   => '192.168.33.10',
                'port'   => 11211,
                'weight' => 100
            )
        ),
    ),
    'redis'     => array(
        "prefix" => "",
        "hosts"  => array(
            array(
                'host'   => '192.168.1.235',
                'port'   => 11211,
                'weight' => 100
            )
        ),
    ),
];