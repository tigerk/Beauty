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
        $lobjtest = new User();
        $users = $lobjtest->find();
        var_dump($users);
    }

}
