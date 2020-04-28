<?php

use Negan\Support\Env;

if (! function_exists('env')) {
    function env($key, $default = null)
    {
        return Env::get($key, $default);
    }
}

if (! function_exists('config')) {
    function config($key)
    {
        return Env::config($key);
    }
}

if (! function_exists('response')) {

    function response($content = '')
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($content, JSON_UNESCAPED_UNICODE);
        exit;
    }

}

