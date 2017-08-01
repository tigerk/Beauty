<?php

class TestController extends \Beauty\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function test()
    {
        \Beauty\Cache\MemcacheClient::getInstance()->tags("tag1")->put("test1", "kimhwawoon");
        $cache = \Beauty\Cache\MemcacheClient::getInstance()->tags("tag1")->get("test2", function () {
            return \Beauty\Model\User::get(10);
        });
        var_dump($cache);
    }

    public function test2(\Beauty\Http\Request $request)
    {
        var_dump($request->getSegment());
    }
}