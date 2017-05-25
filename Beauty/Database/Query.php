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

    protected $table;

    public function __construct()
    {
        $factory   = new Connector\ConnectorFactory();
        $this->pdo = $factory->connection($this->identify);
    }

    /**
     * 获取pdo object
     *
     * @return mixed
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /** Custom SQL Query **/
    function query($sql)
    {
        $stmt = $this->pdo->query($sql);

        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        return $stmt->fetchAll();
    }

    public function update($sql)
    {
        $_stmt = $this->pdo->prepare($sql);
        $_stmt->execute(array());

        return $_stmt->rowCount();
    }

    public function insert($sql)
    {
        $_stmt = $this->pdo->prepare($sql);
        $_stmt->execute(array());

        return $_stmt->rowCount();
    }

}