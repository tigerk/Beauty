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
        $userlist = User::where("user_id","19015189")->get(10);
        var_dump($userlist);
    }

    public function test2(\Beauty\Http\Request $request)
    {
        var_dump($request->getSegment());
    }
}