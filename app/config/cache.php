<?php

return [
    'driver'     => "redis",
    'connection' => null,
    'prefix'     => "",
    'memcached'  => array(
        array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100),
    ),
];