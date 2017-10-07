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

    public function access(Request $request)
    {
        return "you are allowed.";
    }

    public function forbidden(Request $request)
    {
        return "you are forbidden.";
    }
}