<?php

use \Beauty\Model\User;

class TestController extends \Beauty\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function test()
    {
        $user = new User();
        $h = $user->getuser();

        $h->nickname = '测试通2';
        $h->save();

//        var_dump($user);
    }

    public function test2(\Beauty\Http\Request $request)
    {
        var_dump($request->getSegment());
    }
}