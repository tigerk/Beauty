<?php

/**
 * 路由配置
 * 最先设置的路由优先触发，未经验证
 */

use Beauty\Core\App;

/**
 * 菜谱搜索功能
 */
App::router()->get('/reg/[0-9]+/[0-9]+', 'App\Controllers\TestController@test');

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
App::router()->filter(function () {
    return true;
}, function () {
    App::router()->get('/', 'App\Controllers\TestController@test');
});

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
App::router()->filter(function () {
    return false;
}, function () {
    App::router()->get('/hehe', 'TestController@test');
});