<?php

/**
 * 路由配置
 * 最先设置的路由优先触发，未经验证
 */

/**
 * 菜谱搜索功能
 */
\Beauty\App::router()->get('/recipe/s/[0-9]+/[0-9]+', 'RecipeController@searchRecipe');
//\Beauty\App::router()->get('/.*', function (\Beauty\Http\Request $request) {
//    echo 123123;
//});

/**
 * 添加过滤器，过滤器true，不添加到路由器解析中。
 */
\Beauty\App::router()->filter(function () {

}, function () {
    \Beauty\App::router()->get('/hehe', 'RecipeController@searchRecipe');
});