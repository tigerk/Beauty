<?php

/**
 * mysql连接器
 */

namespace Beauty\Database\Connector;

use PDO;

class MysqlConnector
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = array(
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => true,
    );

    /**
     * Create a new PDO connection.
     *
     * @param  string $dsn
     * @param  array $config
     * @param  array $options
     * @return \PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        $username = $config['username'];
        $password = $config['password'];

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Establish a database connection.
     *
     * @param  array $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getHostDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        if (isset($config['unix_socket'])) {
            $connection->exec("use {$config['database']};");
        }

        $collation = $config['collation'];

        // Next we will set the "names" and "collation" on the clients connections so
        // a correct character set will be used by this client. The collation also
        // is set on the server but needs to be set here on this client objects.
        $charset = $config['charset'];

        $names = "set names '$charset'" .
            (!is_null($collation) ? " collate '$collation'" : '');

        $connection->prepare($names)->execute();

        // If the "strict" option has been configured for the connection we'll enable
        // strict mode on all of these tables. This enforces some extra rules when
        // using the MySQL database system and is a quicker way to enforce them.
        if (isset($config['strict']) && $config['strict']) {
            $connection->prepare("set session sql_mode='STRICT_ALL_TABLES'")->execute();
        }

        return $connection;
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param  array $config
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        extract($config);

        return isset($config['port'])
            ? "mysql:host={$host};port={$port};dbname={$database}"
            : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * @param  array $config
     * @return array
     */
    public function getOptions(array $config)
    {
        return $this->options;
    }

}
