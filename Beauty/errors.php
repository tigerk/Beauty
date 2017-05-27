<?php

function handleException(Throwable $e)
{
    DLog::fatal("message:" . $e->getMessage() . " file=" . $e->getFile() . " line=" . $e->getLine(), 0, []);
}


set_exception_handler("handleException");