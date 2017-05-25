<?php

/**
 * load class
 */
require 'vendor/autoload.php';

$app = new \Beauty\App();

$app->get('/', 'TestController@test');
$app->get('/', 'TestController@test');
$app->get('/test', 'TestController@test');

$app->run();