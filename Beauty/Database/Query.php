<?php

namespace Beauty\Database;

use PDO;

class Query
{
    protected $pdo;

    /**
     * db config key
     */
    protected $identify = 'default';

    /**
     * current table name
     *
     * @var
     */
    protected $table;

    /**
     * sql assembler
     * @var
     */
    protected $assembler;

    public function __construct()
    {
        $this->assembler = new Assembler();
    }

    private function _init()
    {
        if (NULL === $this->pdo) {
            $factory   = new Connector\ConnectorFactory();
            $this->pdo = $factory->connection($this->identify);
        }
    }

    /**
     * 获取pdo object
     *
     * @return mixed
     */
    public function getPdo()
    {
        $this->_init();

        return $this->pdo;
    }

    private function query($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param $fields
     * @param null $conds
     * @param null $appends
     * @param null $options
     * @return bool
     */
    public function select($fields, $conds = NULL, $appends = NULL, $options = NULL)
    {
        $this->_init();

        if (empty($conds)) {
            $conds = NULL;
        }
        $sql = $this->assembler->getSelect($this->table, $fields, $conds, $options, $appends);
        if (!$sql) {
            return false;
        }

        return $this->query($sql);
    }

    /**
     * get one row
     *
     * @param $fields
     * @param null $conds
     * @param null $options
     * @return bool
     */
    public function find($fields, $conds = NULL, $options = NULL)
    {
        $this->_init();

        if (empty($conds)) {
            $conds = NULL;
        }

        $appends = "limit 1";

        $sql = $this->assembler->getSelect($this->table, $fields, $conds, $options, $appends);
        if (!$sql) {
            return false;
        }

        return $this->query($sql);
    }

    public function update($row, $conds = NULL, $appends = NULL, $options = NULL)
    {
        $this->_init();
        if (empty($conds)) {
            $conds = NULL;
        }
        $sql  = $this->assembler->getUpdate($this->table, $row, $conds, $options, $appends);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array());

        return $stmt->rowCount();
    }

    public function insert($row, $options = NULL, $onDup = NULL)
    {
        $this->_init();
        if (empty($conds)) {
            $conds = NULL;
        }
        $sql  = $this->assembler->getInsert($this->table, $row, $options, $onDup);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array());

        return $stmt->rowCount();
    }

    final function getInsertID()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取最近一次查询的SQL语句
     */
    public function getLastSQL()
    {
        return $this->assembler->getSQL();
    }
}