<?php

/**
 * load class
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * set logger sub directory
 */
\Beauty\Log\DLog::setLogger("Beauty");

/**
 * 定义变量
 */
$app = new \Beauty\Core\App(__DIR__ . "/../");

/**
 * 加载路由文件
 */
require base_path() . 'routes/web.php';

$app->run();