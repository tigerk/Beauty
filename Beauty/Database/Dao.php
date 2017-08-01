<?php

/**
 * Mysqli Model wrapper
 *
 * @category  Database Access
 * @package   MysqliDb
 * @author    Alexander V. Butenko <a.butenka@gmail.com>
 * @copyright Copyright (c) 2015
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      http://github.com/joshcam/PHP-MySQLi-Database-Class
 * @version   2.6-master
 *
 * @method int count ()
 * @method Dao ArrayBuilder()
 * @method Dao JsonBuilder()
 * @method Dao ObjectBuilder()
 * @method mixed byId (string $id, mixed $fields)
 * @method mixed get (mixed $limit, mixed $fields)
 * @method mixed getOne (mixed $fields)
 * @method mixed paginate (int $page, array $fields)
 * @method Dao query ($query, $numRows)
 * @method Dao rawQuery ($query, $bindParams, $sanitize)
 * @method Dao join (string $objectName, string $key, string $joinType, string $primaryKey)
 * @method Dao with (string $objectName)
 * @method Dao groupBy (string $groupByField)
 * @method Dao orderBy ($orderByField, $orderbyDirection, $customFields)
 * @method Dao where ($whereProp, $whereValue, $operator)
 * @method Dao orWhere ($whereProp, $whereValue, $operator)
 * @method Dao setQueryOption ($options)
 * @method Dao setTrace ($enabled, $stripPrefix)
 * @method Dao withTotalCount ()
 * @method Dao startTransaction ()
 * @method Dao commit ()
 * @method Dao rollback ()
 * @method Dao ping ()
 * @method string getLastError ()
 * @method string getLastQuery ()
 **/

namespace Beauty\Database;

use Beauty\Database\Connector\MysqlConnector;

abstract class Dao
{
    /**
     * Working instance of MysqliDb created earlier
     *
     * @var MysqlClient
     */
    private $dbClient;
    /**
     * An array that holds object data
     *
     * @var array
     */
    public $data;
    /**
     * Flag to define is object is new or loaded from database
     *
     * @var boolean
     */
    public $isNew = true;
    /**
     * Return type: 'Array' to return results as array, 'Object' as object
     * 'Json' as json string
     *
     * @var string
     */
    public $returnType = 'Object';
    /**
     * An array that holds has* objects which should be loaded togeather with main
     * object togeather with main object
     *
     * @var string
     */
    private $_with = Array();
    /**
     * Per page limit for pagination
     *
     * @var int
     */
    protected $pageLimit = 20;
    /**
     * Variable that holds total pages count of last paginate() query
     *
     * @var int
     */
    public static $totalPages = 0;
    /**
     * An array that holds insert/update/select errors
     *
     * @var array
     */
    public $errors = null;
    /**
     * Primary key for an object. 'id' is a default value.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Table name for an object. Class name will be used by default
     *
     * @var string
     */
    protected $dbTable;

    /**
     * current table connection name.
     *
     * @var string
     */
    protected $connection = "default";

    /**
     * current connection channel, master or slave
     *
     * @var string
     */
    protected $channel = MysqlConnector::QUERY_SLAVE_CHANNEL;

    /**
     * The hook event.
     *
     * @var array
     */
    protected static $hooks;

    /**
     * @param array $data Data to preload on object creation
     */
    public function __construct($data = null)
    {
        if (empty ($this->dbTable)) {
            throw new \Exception("you must confirm table name.");
        }

        static::booting();

        if ($data) {
            $this->data = $data;
        }

        $this->dbClient = MysqlClient::getInstance($this->connection);
    }


    protected static function booting()
    {
    }

    /**
     * Register a created model event
     *
     * @param  \Closure|string $callback
     * @return void
     */
    public static function created($callback)
    {
        $name  = get_called_class();
        $event = "created";
        self::addHooks("model.{$name}.{$event}", $callback);
    }

    /**
     * Register a created model event
     *
     * @param  \Closure|string $callback
     * @return void
     */
    public static function updated($callback)
    {
        $name  = get_called_class();
        $event = "updated";
        self::addHooks("model.{$name}.{$event}", $callback);
    }

    protected static function addHooks($event, $callback)
    {
        self::$hooks[$event] = $callback;
    }

    /**
     * Fire the given hook for the model.
     *
     * @param  string $event
     * @param  bool $halt
     * @return mixed
     */
    protected function fireModelHook($event)
    {
        if (!isset(self::$hooks)) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "model." . get_class($this) . ".{$event}";

        return call_user_func_array(self::$hooks[$event], [$this]);
    }

    /**
     * get master db connection
     */
    public function getMysqlConnection()
    {
        $this->dbClient = MysqlClient::getInstance($this->connection);

        return $this->dbClient;
    }

    /**
     * Magic setter function
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        if (property_exists($this, 'hidden') && array_search($name, $this->hidden) !== false) {
            return;
        }

        $this->data[$name] = $value;
    }

    /**
     * Magic getter function
     *
     * @param $name string name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, 'hidden') && array_search($name, $this->hidden) !== false) {
            return null;
        }

        if (isset ($this->data[$name]) && $this->data[$name] instanceof Dao) {
            return $this->data[$name];
        }

        if (isset ($this->data[$name])) {
            return $this->data[$name];
        }

        if (property_exists($this->dbClient, $name)) {
            return $this->dbClient->$name;
        }
    }

    public function __isset($name)
    {
        if (isset ($this->data[$name])) {
            return isset ($this->data[$name]);
        }

        if (property_exists($this->dbClient, $name)) {
            return isset ($this->dbClient->$name);
        }
    }

    public function __unset($name)
    {
        unset ($this->data[$name]);
    }

    /**
     * 设置数据库连接
     *
     * @param $connection
     * @return $this
     */
    private function on($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * 设置数据库的主从
     *
     * @param $channel
     * @return $this
     */
    private function channel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return mixed insert id or false in case of failure
     */
    public function insert()
    {
        if (!empty ($this->timestamps) && in_array("created_at", $this->timestamps)) {
            $this->created_at = date("Y-m-d H:i:s");
        }

        /**
         * 连接主库
         */
        $this->dbClient->setQueryChannel(MysqlConnector::QUERY_MASTER_CHANNEL);

        $sqlData = $this->prepareData();
        if (!$this->validate($sqlData)) {
            return false;
        }

        $id = $this->dbClient->insert($this->dbTable, $sqlData);
        if (!empty ($this->primaryKey) && empty ($this->data[$this->primaryKey])) {
            $this->data[$this->primaryKey] = $id;
        }

        $this->isNew = false;

        return $id;
    }

    /**
     * @param array $data Optional update data to apply to the object
     */
    public function update($data = null)
    {
        if (empty ($this->dbFields)) {
            return false;
        }

        if (empty ($this->data[$this->primaryKey])) {
            return false;
        }

        if ($data) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }

        if (!empty ($this->timestamps) && in_array("updated_at", $this->timestamps)) {
            $this->updated_at = date("Y-m-d H:i:s");
        }

        /**
         * 主库
         */
        $this->dbClient->setQueryChannel(MysqlConnector::QUERY_MASTER_CHANNEL);

        $sqlData = $this->prepareData();
        if (!$this->validate($sqlData)) {
            return false;
        }

        $this->getMysqlConnection()->where($this->primaryKey, $this->data[$this->primaryKey]);

        $ret = $this->dbClient->update($this->dbTable, $sqlData);

        if ($ret) {
            $this->fireModelHook('updated');
        }

        return $ret;
    }

    /**
     * Save or Update object
     *
     * @return mixed insert id or false in case of failure
     */
    public function save($data = null)
    {
        if ($this->isNew) {
            return $this->insert();
        }

        return $this->update($data);
    }

    /**
     * Delete method. Works only if object primaryKey is defined
     *
     * @return boolean Indicates success. 0 or 1.
     */
    public function delete()
    {
        if (empty ($this->data[$this->primaryKey])) {
            return false;
        }

        $this->dbClient->where($this->primaryKey, $this->data[$this->primaryKey]);

        return $this->dbClient->delete($this->dbTable);
    }

    /**
     * Get object by primary key.
     *
     * @access public
     * @param $id string Primary Key
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return Dao
     */
    private function find($id, $fields = null)
    {
        $this->dbClient->where($this->dbTable . '.' . $this->primaryKey, $id);

        return $this->getOne($fields);
    }

    /**
     * convenient function to fetch one object. Mostly will be together with where()
     *
     * @access public
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return Dao
     */
    protected function getOne($fields = null)
    {
        $this->dbClient->setQueryChannel($this->channel);
        $results = $this->dbClient->arrayBuilder()->getOne($this->dbTable, $fields);
        if ($this->dbClient->count == 0)
            return null;

        $this->processArrays($results);
        $this->data = $results;

        $item        = new static($results);
        $item->isNew = false;

        return $item;
    }

    /**
     * Fetch all objects
     *
     * @access public
     * @param integer|array $limit Array to define SQL limit in format Array ($count, $offset)
     *                             or only $count
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return array Array of Clients
     */
    protected function get($limit = null, $fields = null)
    {
        $this->dbClient->setQueryChannel($this->channel);

        $results = $this->dbClient->arrayBuilder()->get($this->dbTable, $limit, $fields);
        if ($this->dbClient->count == 0) {
            return null;
        }

        $objects = [];
        foreach ($results as &$r) {
            $this->processArrays($r);
            $this->data  = $r;
            $item        = new static ($r);
            $item->isNew = false;
            $objects[]   = $item;
        }

        $this->_with = [];

        return $objects;
    }

    /**
     * Function to get a total records count
     *
     * @return int
     */
    protected function count()
    {
        $this->dbClient->setQueryChannel($this->channel);

        $res = $this->dbClient->arrayBuilder()->getValue($this->dbTable, "count(*)");
        if (!$res) {
            return 0;
        }

        return $res;
    }

    /**
     * Pagination wraper to get()
     *
     * @access public
     * @param int $page Page number
     * @param array|string $fields Array or coma separated list of fields to fetch
     * @return array
     */
    private function paginate($page, $fields = null)
    {
        $this->dbClient->pageLimit = $this->pageLimit;
        $res                       = $this->dbClient->paginate($this->dbTable, $page, $fields);
        self::$totalPages          = $this->dbClient->totalPages;
        if ($this->dbClient->count == 0) {
            return null;
        }

        $objects = [];
        foreach ($res as &$r) {
            $this->processArrays($r);
            $this->data  = $r;
            $item        = new static ($r);
            $item->isNew = false;
            $objects[]   = $item;
        }

        $this->_with = [];

        return $objects;
    }

    /**
     * Catches calls to undefined methods.
     *
     * Provides magic access to private functions of the class and native public mysqlidb functions
     *
     * @param string $method
     * @param mixed $arg
     *
     * @return mixed
     */
    public function __call($method, $arg)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arg);
        }

        call_user_func_array(array($this->dbClient, $method), $arg);

        return $this;
    }

    /**
     * Catches calls to undefined static methods.
     *
     * Transparently creating Client class to provide smooth API like name::get() name::orderBy()->get()
     *
     * @param string $method
     * @param mixed $arg
     *
     * @return mixed
     */
    public static function __callStatic($method, $arg)
    {
        $obj    = new static;
        $result = call_user_func_array(array($obj, $method), $arg);
        if (method_exists($obj, $method)) {
            return $result;
        }

        return $obj;
    }

    /**
     * Converts object data to an associative array.
     *
     * @return array Converted data
     */
    public function toArray()
    {
        $data = $this->data;
        foreach ($data as &$d) {
            if ($d instanceof Dao)
                $d = $d->data;
        }

        return $data;
    }

    /**
     * Converts object data to a JSON string.
     *
     * @return string Converted data
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts object data to a JSON string.
     *
     * @return string Converted data
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param array $data
     */
    private function processArrays(&$data)
    {
        if (isset ($this->jsonFields) && is_array($this->jsonFields)) {
            foreach ($this->jsonFields as $key) {
                $data[$key] = json_decode($data[$key]);
            }
        }

        if (isset ($this->arrayFields) && is_array($this->arrayFields)) {
            foreach ($this->arrayFields as $key) {
                $data[$key] = explode("|", $data[$key]);
            }
        }
    }

    /**
     * @param array $data
     */
    private function validate($data)
    {
        if (!$this->dbFields) {
            return true;
        }

        foreach ($this->dbFields as $key => $desc) {
            $type     = null;
            $required = false;
            if (isset ($data[$key]))
                $value = $data[$key];
            else
                $value = null;

            if (is_array($value))
                continue;

            if (isset ($desc[0]))
                $type = $desc[0];
            if (isset ($desc[1]) && ($desc[1] == 'required'))
                $required = true;

            if ($required && strlen($value) == 0) {
                $this->errors[] = Array($this->dbTable . "." . $key => "is required");
                continue;
            }
            if ($value == null)
                continue;

            switch ($type) {
                case "text";
                    $regexp = null;
                    break;
                case "int":
                    $regexp = "/^[0-9]*$/";
                    break;
                case "double":
                    $regexp = "/^[0-9\.]*$/";
                    break;
                case "bool":
                    $regexp = '/^[yes|no|0|1|true|false]$/i';
                    break;
                case "datetime":
                    $regexp = "/^[0-9a-zA-Z -:]*$/";
                    break;
                default:
                    $regexp = $type;
                    break;
            }
            if (!$regexp)
                continue;

            if (!preg_match($regexp, $value)) {
                $this->errors[] = Array($this->dbTable . "." . $key => "$type validation failed");
                continue;
            }
        }

        return !count($this->errors) > 0;
    }

    private function prepareData()
    {
        $this->errors = [];
        $sqlData      = [];
        if (count($this->data) == 0)
            return [];

        if (method_exists($this, "preLoad")) {
            $this->preLoad($this->data);
        }

        if (!$this->dbFields) {
            return $this->data;
        }

        foreach ($this->data as $key => &$value) {
            if ($value instanceof Dao && $value->isNew == true) {
                $id = $value->save();
                if ($id)
                    $value = $id;
                else
                    $this->errors = array_merge($this->errors, $value->errors);
            }

            if (!in_array($key, array_keys($this->dbFields)))
                continue;

            if (!is_array($value)) {
                $sqlData[$key] = $value;
                continue;
            }

            if (isset ($this->jsonFields) && in_array($key, $this->jsonFields))
                $sqlData[$key] = json_encode($value);
            else if (isset ($this->arrayFields) && in_array($key, $this->arrayFields))
                $sqlData[$key] = implode("|", $value);
            else
                $sqlData[$key] = $value;
        }

        return $sqlData;
    }
}