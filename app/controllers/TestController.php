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

        var_dump($h);
    }

    public function test2(\Beauty\Http\Request $request)
    {
        var_dump($request->getSegment());
    }
}