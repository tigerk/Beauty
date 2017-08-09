<?php

class TestController extends \Beauty\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function test(\Beauty\Http\Request $request)
    {
        return "test";
    }
}