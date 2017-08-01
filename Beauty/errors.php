<?php

function handleException(Throwable $e)
{
    DLog::fatal(var_export($e, true), 0, []);
}