<?php

/**
 * $app 默认不允许修改
 */

/**
 * 菜谱搜索功能
 */
$app->get('/recipe/s/[0-9]+/[0-9]+', 'RecipeController@searchRecipe');

//$app->get('/kimhwawoon+', 'TestController@test2');
//$app->get('/[0-9]+/12', 'TestController@test');
//$app->get('/func/a/b/c/d', function (\Beauty\Http\Request $request) {
//    var_dump($request->getSegment());
//});