<?php

namespace Negan\Foundation;

class Config
{

    public static function init()
    {
        self::do_debug();
        self::do_timezone();
    }

    private static function do_debug()
    {
        $debug = env('APP_DEBUG', false);
        if ( $debug ){
            error_reporting(-1);
            ini_set('display_error','On');
        } else {
            error_reporting(0);
            ini_set('display_error','Off');
        }
    }

    private static function do_timezone()
    {
        $timezone = env('APP_TIMEZONE', 'UTC');
        if ( !date_default_timezone_set($timezone) ) {
            date_default_timezone_set('UTC');
        }
    }

}