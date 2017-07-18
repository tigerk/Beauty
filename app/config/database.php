<?php

/**
 * 配置数据库
 */

return [
    /**
     * 默认数据库配置
     */
    "default"     => [
        "master" => [
            [
                'host'      => '192.168.1.235',
                'database'  => 'dg2010',
                'username'  => 'root',
                'password'  => 'douguo2015',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        ],
        "slave"  => [
            [
                'host'      => '192.168.1.235',
                'database'  => 'dg2010',
                'username'  => 'root',
                'password'  => 'douguo2015',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        ]
    ],
    "douguo_core" => [
        "master" => [
            [
                'host'      => '192.168.1.235',
                'database'  => 'dg2010',
                'username'  => 'root',
                'password'  => 'douguo2015',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        ],
        "slave"  => [
            [
                'host'      => '192.168.1.235',
                'database'  => 'dg2010',
                'username'  => 'root',
                'password'  => 'douguo2015',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        ]
    ]
];