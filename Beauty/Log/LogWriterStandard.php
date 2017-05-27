<?php

/**
 * 输出日志到标准输出
 */
class LogWriterStandard implements Writer
{
    public function write($str)
    {
        //给点颜色看吧
        $sapi_type = php_sapi_name();
        if ($sapi_type == 'cli') {
            $str = preg_replace("/^([FW]\w+)/", "\033[31m\\1\033[0m", $str);
            $str = preg_replace("/^([N]\w+)/", "\033[32m\\1\033[0m", $str);
            $str = preg_replace("/^([TD]\w+)/", "\033[33m\\1\033[0m", $str);
            $str = preg_replace("/(\w+)\=/", "\033[34m\\1\033[0m=", $str);
        }
        if (substr($sapi_type, -3, 3) == 'cgi') {
            $str .= '<br>';
        }
        echo urldecode($str);
    }
}
