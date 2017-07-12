<?php

/**
 * $app 默认不允许修改
 */

$app->get('/', 'TestController@test');
$app->get('/kimhwawoon+', 'TestController@test2');
$app->get('/[0-9]+/12', 'TestController@test');
$app->get('/func/a/b/c/d', function (\Beauty\Http\Request $request) {
    var_dump($request->getSegment());
});