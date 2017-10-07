<?php

/**
 * 路由配置
 * 最先设置的路由优先触发，未经验证
 */

use Beauty\Core\App;

/**
 * 默认
 */
App::router()->get('/', function () {
    echo "Hello, world";
});

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
App::router()->filter(function () {
    return true;
}, function () {
    App::router()->get('/access', 'App\Controllers\TestController@access');
});

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
App::router()->filter(function () {
    return false;
}, function () {
    App::router()->get('/forbidden', 'App\Controllers\TestController@forbidden');
});