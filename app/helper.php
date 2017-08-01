<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 2017/8/1
 * Time: 15:39
 */


if (!function_exists("get_common_header")) {
    function get_common_header()
    {
        return [
            "version" => $_SERVER['HTTP_VERSION'],
        ];
    }
}