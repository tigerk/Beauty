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
        $user           = new User();
        $user->pwd      = 'heheda';
        $user->nickname = 'heheda';
        $id             = $user->save();
        if ($id)
            echo "user created with id = " . $id;

        var_dump($user);
    }

    public function test2(\Beauty\Http\Request $request)
    {
        var_dump($request->getSegment());
    }
}