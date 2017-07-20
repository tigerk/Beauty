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
     * Models path
     *
     * @var modelPath
     */
    protected static $modelPath;
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
    public $returnType = 'Array';
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
    public static $pageLimit = 20;
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
     * @var stating
     */
    protected $primaryKey = 'id';
    /**
     * Table name for an object. Class name will be used by default
     *
     * @var stating
     */
    protected $dbTable;

    /**
     * current table connection name.
     *
     * @var string
     */
    protected $connection = "default";

    /**
     * @param array $data Data to preload on object creation
     */
    public function __construct($data = null)
    {
        if (empty ($this->dbTable)) {
            $this->dbTable = get_class($this);
        }

        if ($data) {
            $this->data = $data;
        }
    }

    /**
     * get master db connection
     */
    public function getMasterConnection()
    {
        $this->dbClient = MysqlClient::getInstance($this->connection, "master");
    }

    /**
     * get slave db connection
     */
    public function getSlaveConnection()
    {
        $this->dbClient = MysqlClient::getInstance($this->connection, "slave");
    }

    /**
     * Magic setter function
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        if (property_exists($this, 'hidden') && array_search($name, $this->hidden) !== false)
            return;

        $this->data[$name] = $value;
    }

    /**
     * Magic getter function
     *
     * @param $name Variable name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, 'hidden') && array_search($name, $this->hidden) !== false)
            return null;

        if (isset ($this->data[$name]) && $this->data[$name] instanceof Dao)
            return $this->data[$name];

        if (property_exists($this, 'relations') && isset ($this->relations[$name])) {
            $relationType = strtolower($this->relations[$name][0]);
            $modelName    = $this->relations[$name][1];
            switch ($relationType) {
                case 'hasone':
                    $key             = isset ($this->relations[$name][2]) ? $this->relations[$name][2] : $name;
                    $obj             = new $modelName;
                    $obj->returnType = $this->returnType;

                    return $this->data[$name] = $obj->byId($this->data[$key]);
                    break;
                case 'hasmany':
                    $key             = $this->relations[$name][2];
                    $obj             = new $modelName;
                    $obj->returnType = $this->returnType;

                    return $this->data[$name] = $obj->where($key, $this->data[$this->primaryKey])->get();
                    break;
                default:
                    break;
            }
        }

        if (isset ($this->data[$name]))
            return $this->data[$name];

        if (property_exists($this->dbClient, $name))
            return $this->dbClient->$name;
    }

    public function __isset($name)
    {
        if (isset ($this->data[$name]))
            return isset ($this->data[$name]);

        if (property_exists($this->dbClient, $name))
            return isset ($this->dbClient->$name);
    }

    public function __unset($name)
    {
        unset ($this->data[$name]);
    }

    /**
     * Helper function to create Client with Json return type
     *
     * @return Dao
     */
    private function JsonBuilder()
    {
        $this->returnType = 'Json';

        return $this;
    }

    /**
     * Helper function to create Client with Array return type
     *
     * @return Dao
     */
    private function ArrayBuilder()
    {
        $this->returnType = 'Array';

        return $this;
    }

    /**
     * Helper function to create Client with Object return type.
     * Added for consistency. Works same way as new $objname ()
     *
     * @return Dao
     */
    private function ObjectBuilder()
    {
        $this->returnType = 'Object';

        return $this;
    }

    /**
     * Helper function to create a virtual table class
     *
     * @param string tableName Table name
     * @return Dao
     */
    public static function table($tableName)
    {
        $tableName = preg_replace("/[^-a-z0-9_]+/i", '', $tableName);
        if (!class_exists($tableName)) {
            eval ("class $tableName extends \Beauty\Database\Dao {}");
        }

        return new $tableName ();
    }

    /**
     * @return mixed insert id or false in case of failure
     */
    public function insert()
    {
        $this->getMasterConnection();

        if (!empty ($this->timestamps) && in_array("createdAt", $this->timestamps)) {
            $this->createdAt = date("Y-m-d H:i:s");
        }

        $sqlData = $this->prepareData();
        if (!$this->validate($sqlData)) {
            return false;
        }

        $id = $this->dbClient->insert($this->dbTable, $sqlData);
        if (!empty ($this->primaryKey) && empty ($this->data[$this->primaryKey]))
            $this->data[$this->primaryKey] = $id;
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

        if (!empty ($this->timestamps) && in_array("updatedAt", $this->timestamps)) {
            $this->updatedAt = date("Y-m-d H:i:s");
        }

        $this->getMasterConnection();

        $sqlData = $this->prepareData();
        if (!$this->validate($sqlData)) {
            return false;
        }

        $this->dbClient->where($this->primaryKey, $this->data[$this->primaryKey]);

        return $this->dbClient->update($this->dbTable, $sqlData);
    }

    /**
     * Save or Update object
     *
     * @return mixed insert id or false in case of failure
     */
    public function save($data = null)
    {
        if ($this->isNew)
            return $this->insert();

        return $this->update($data);
    }

    /**
     * Delete method. Works only if object primaryKey is defined
     *
     * @return boolean Indicates success. 0 or 1.
     */
    public function delete()
    {
        if (empty ($this->data[$this->primaryKey]))
            return false;

        $this->dbClient->where($this->primaryKey, $this->data[$this->primaryKey]);

        return $this->dbClient->delete($this->dbTable);
    }

    /**
     * Get object by primary key.
     *
     * @access public
     * @param $id Primary Key
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return Dao|array
     */
    private function find($id, $fields = null)
    {
        $this->getSlaveConnection();
        $this->dbClient->where(MysqlClient::$prefix . $this->dbTable . '.' . $this->primaryKey, $id);

        return $this->getOne($fields);
    }

    /**
     * Convinient function to fetch one object. Mostly will be togeather with where()
     *
     * @access public
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return Dao
     */
    protected function getOne($fields = null)
    {
        $this->processHasOneWith();
        $results = $this->dbClient->ArrayBuilder()->getOne($this->dbTable, $fields);
        if ($this->dbClient->count == 0)
            return null;

        $this->processArrays($results);
        $this->data = $results;
        $this->processAllWith($results);

        $item        = new static ($results);
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
        $objects = [];
        $this->getSlaveConnection();
        $this->processHasOneWith();

        $results = $this->dbClient->ArrayBuilder()->get($this->dbTable, $limit, $fields);
        if ($this->dbClient->count == 0)
            return null;

        foreach ($results as &$r) {
            $this->processArrays($r);
            $this->data = $r;
            $this->processAllWith($r, false);
            if ($this->returnType == 'Object') {
                $item        = new static ($r);
                $item->isNew = false;
                $objects[]   = $item;
            }
        }
        $this->_with = Array();
        if ($this->returnType == 'Object')
            return $objects;

        if ($this->returnType == 'Json')
            return json_encode($results);

        return $results;
    }

    /**
     * Function to set witch hasOne or hasMany objects should be loaded togeather with a main object
     *
     * @access public
     * @param string $objectName Object Name
     *
     * @return Dao
     */
    private function with($objectName)
    {
        if (!property_exists($this, 'relations') && !isset ($this->relations[$name]))
            die ("No relation with name $objectName found");

        $this->_with[$objectName] = $this->relations[$objectName];

        return $this;
    }

    /**
     * Function to join object with another object.
     *
     * @access public
     * @param string $objectName Object Name
     * @param string $key Key for a join from primary object
     * @param string $joinType SQL join type: LEFT, RIGHT,  INNER, OUTER
     * @param string $primaryKey SQL join On Second primaryKey
     *
     * @return Dao
     */
    private function join($objectName, $key = null, $joinType = 'LEFT', $primaryKey = null)
    {
        $joinObj = new $objectName;
        if (!$key)
            $key = $objectName . "id";

        if (!$primaryKey)
            $primaryKey = MysqlClient::$prefix . $joinObj->dbTable . "." . $joinObj->primaryKey;

        if (!strchr($key, '.'))
            $joinStr = MysqlClient::$prefix . $this->dbTable . ".{$key} = " . $primaryKey;
        else
            $joinStr = MysqlClient::$prefix . "{$key} = " . $primaryKey;

        $this->dbClient->join($joinObj->dbTable, $joinStr, $joinType);

        return $this;
    }

    /**
     * Function to get a total records count
     *
     * @return int
     */
    protected function count()
    {
        $res = $this->dbClient->ArrayBuilder()->getValue($this->dbTable, "count(*)");
        if (!$res)
            return 0;

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
        $this->dbClient->pageLimit = self::$pageLimit;
        $res                       = $this->dbClient->paginate($this->dbTable, $page, $fields);
        self::$totalPages          = $this->dbClient->totalPages;
        if ($this->dbClient->count == 0) return null;

        foreach ($res as &$r) {
            $this->processArrays($r);
            $this->data = $r;
            $this->processAllWith($r, false);
            if ($this->returnType == 'Object') {
                $item        = new static ($r);
                $item->isNew = false;
                $objects[]   = $item;
            }
        }
        $this->_with = Array();
        if ($this->returnType == 'Object')
            return $objects;

        if ($this->returnType == 'Json')
            return json_encode($res);

        return $res;
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
        if (method_exists($this, $method))
            return call_user_func_array(array($this, $method), $arg);

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
        if (method_exists($obj, $method))
            return $result;

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
        $this->processAllWith($data);
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
     * Function queries hasMany relations if needed and also converts hasOne object names
     *
     * @param array $data
     */
    private function processAllWith(&$data, $shouldReset = true)
    {
        if (count($this->_with) == 0)
            return;

        foreach ($this->_with as $name => $opts) {
            $relationType = strtolower($opts[0]);
            $modelName    = $opts[1];
            if ($relationType == 'hasone') {
                $obj        = new $modelName;
                $table      = $obj->dbTable;
                $primaryKey = $obj->primaryKey;

                if (!isset ($data[$table])) {
                    $data[$name] = $this->$name;
                    continue;
                }
                if ($data[$table][$primaryKey] === null) {
                    $data[$name] = null;
                } else {
                    if ($this->returnType == 'Object') {
                        $item             = new $modelName ($data[$table]);
                        $item->returnType = $this->returnType;
                        $item->isNew      = false;
                        $data[$name]      = $item;
                    } else {
                        $data[$name] = $data[$table];
                    }
                }
                unset ($data[$table]);
            } else
                $data[$name] = $this->$name;
        }
        if ($shouldReset)
            $this->_with = Array();
    }

    /*
     * Function building hasOne joins for get/getOne method
     */
    private function processHasOneWith()
    {
        if (count($this->_with) == 0)
            return;
        foreach ($this->_with as $name => $opts) {
            $relationType = strtolower($opts[0]);
            $modelName    = $opts[1];
            $key          = null;
            if (isset ($opts[2]))
                $key = $opts[2];
            if ($relationType == 'hasone') {
                $this->dbClient->setQueryOption("MYSQLI_NESTJOIN");
                $this->join($modelName, $key);
            }
        }
    }

    /**
     * @param array $data
     */
    private function processArrays(&$data)
    {
        if (isset ($this->jsonFields) && is_array($this->jsonFields)) {
            foreach ($this->jsonFields as $key)
                $data[$key] = json_decode($data[$key]);
        }

        if (isset ($this->arrayFields) && is_array($this->arrayFields)) {
            foreach ($this->arrayFields as $key)
                $data[$key] = explode("|", $data[$key]);
        }
    }

    /**
     * @param array $data
     */
    private function validate($data)
    {
        if (!$this->dbFields)
            return true;

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
        $this->errors = Array();
        $sqlData      = Array();
        if (count($this->data) == 0)
            return Array();

        if (method_exists($this, "preLoad"))
            $this->preLoad($this->data);

        if (!$this->dbFields)
            return $this->data;

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