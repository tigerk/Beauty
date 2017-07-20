<?php

error_reporting(E_ALL & ~E_NOTICE);

/**
 * load class
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * set logger sub directory
 */
DLog::setLogger("Beauty");

$app = new \Beauty\App();

/**
 * 加载路由文件
 */
require app_path() . 'routes.php';

$app->run();