<?php
namespace Level\VOR;

function log($msg)
{
    if (php_sapi_name() == 'cli')
        fwrite(STDOUT, $msg . PHP_EOL);
    else
        echo $msg, '<br>';
}

function logf($msg, ...$params)
{
    log(sprintf($msg, ...$params));
}

function getParam($pname, $def = null)
{
    return array_key_exists($pname, $_GET) ? $_GET[$pname] : $def;
}
