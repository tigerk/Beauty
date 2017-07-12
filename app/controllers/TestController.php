<?php

class TestController extends \Beauty\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 测试获取数据
     */
    public function test()
    {
        echo 123;die();
    }

    /**
     * 测试获取数据
     */
    public function test2()
    {
        echo 'test2';
    }
}
