<?php

namespace Beauty\Database\Connector;


class ConnectorFactory
{
    /**
     * The active connection instances.
     *
     * @var array
     */
    static $connections = array();

    /**
     * Get a database connection instance.
     *
     * @param  string $name
     * @return object
     */
    public function connection($name = null)
    {
        // If we haven't created this connection, we'll create it based on the config
        // provided in the application.
        if (!isset(self::$connections[$name])) {
            $config     = \Beauty\App::config()->get('database');
            $connect    = new MysqlConnector();
            $connection = $connect->connect($config[$name]);

            self::$connections[$name] = $connection;
        }

        return self::$connections[$name];
    }
}