<?php

namespace Negan\Foundation;

class Config
{
    protected static $config = array();

    public static function set($key, $default = null)
    {
        self::$config[$key] = $default;
    }

    public static function get($key)
    {
        $key = explode( '.', $key );
        $value = self::$config;
        while ( $key ) {
            $k = array_shift( $key );
            if ( !isset($value[$k]) ) {
                return null;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public static function init()
    {
        self::do_debug();
        self::do_timezone();
    }

    private static function do_debug()
    {
        $debug = config('app.debug');
        if ( $debug ) {
            error_reporting(-1);
            ini_set('display_error','On');
        } else {
            error_reporting(0);
            ini_set('display_error','Off');
        }
    }

    private static function do_timezone()
    {
        $timezone = config('app.timezone');
        if ( !date_default_timezone_set($timezone) ) {
            date_default_timezone_set('UTC');
        }
    }

}