<?php

/**
 * load class
 */
require 'vendor/autoload.php';

/**
 * set logger sub directory
 */
DLog::setLogger("Beauty");

$app = new \Beauty\App();

$app->get('/', 'TestController@test');
$app->get('/', 'TestController@test');
$app->get('/test', 'TestController@test');

$app->run();