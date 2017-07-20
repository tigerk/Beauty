<?php

namespace Beauty\Database\Connector;


class MysqlConnector
{
    CONST QUERY_MASTER_CHANNEL = "master";
    CONST QUERY_SLAVE_CHANNEL  = "slave";

    /**
     * The active connection instances.
     *
     * @var array
     */
    static $connections = [];

    /**
     * The connection config
     *
     * @var array
     */
    protected $connectionsSettings = [];

    function __construct()
    {
        $this->connectionsSettings = \Beauty\App::config()->get('database');
    }

    /**
     * Get a database connection instance.
     *
     * @param null $connectionName
     * @param  string $channel
     * @return object
     * @throws \Exception
     * @internal param string $name
     */
    public function connection($connectionName = null, $channel = self::QUERY_MASTER_CHANNEL)
    {
        // If we haven't created this connection, we'll create it based on the config
        // provided in the application.
        if ((self::$connections[$connectionName][$channel])) {
            return self::$connections[$connectionName][$channel];
        }

        if (!isset($this->connectionsSettings[$connectionName])) {
            throw new \Exception('Connection profile not set');
        }

        $index   = array_rand($this->connectionsSettings[$connectionName][$channel]);
        $params  = $this->connectionsSettings[$connectionName][$channel][$index];
        $charset = $params['charset'];

        if (empty($params['host'])) {
            throw new \Exception('MySQL host or socket is not set');
        }

        $mysqli = new \mysqli($params['host'], $params['username'], $params['password'], $params['database'], $params['port']);
        if ($mysqli->connect_error) {
            throw new \Exception('Connect Error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error, $mysqli->connect_errno);
        }

        if (!empty($charset)) {
            $mysqli->set_charset($charset);
        }

        self::$connections[$connectionName][$channel] = $mysqli;

        return self::$connections[$connectionName][$channel];
    }

    /**
     * A method to disconnect from the database
     *
     * @params string $connection connection name to disconnect
     * @throws \Exception
     * @return void
     */
    public function disconnectAll()
    {
        foreach (self::$connections as $n => $conn) {
            foreach ($conn as $channel => $cn) {
                self::$connections[$n][$channel]->close();
            }
        }

        self::$connections = null;
    }
}