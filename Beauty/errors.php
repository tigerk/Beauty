<?php

function handleException(Throwable $e)
{
    DLog::fatal(var_export($e, true), 0, []);
}

set_exception_handler("handleException");