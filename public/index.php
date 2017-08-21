<?php

/**
 * load class
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * set logger sub directory
 */
DLog::setLogger("Beauty");

$app = new \Beauty\App();

/**
 * 加载路由文件
 */
require base_path() . 'routes/web.php';

$app->run();