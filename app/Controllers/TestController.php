<?php

namespace App\Controllers;

use Beauty\Core\Controller;
use Beauty\Http\Request;

class TestController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function test()
    {
        return "test";
    }
}