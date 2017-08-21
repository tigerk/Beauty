<?php

/**
 * 路由配置
 * 最先设置的路由优先触发，未经验证
 */

/**
 * 菜谱搜索功能
 */
\Beauty\App::router()->get('/reg/[0-9]+/[0-9]+', 'TestController@test');

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
\Beauty\App::router()->filter(function () {
    return true;
}, function () {
    \Beauty\App::router()->get('/', 'TestController@test');
});


/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
\Beauty\App::router()->filter(function () {
    return false;
}, function () {
    \Beauty\App::router()->get('/hehe', 'TestController@test');
});