<?php

namespace Negan\Support;

class Env
{
    protected static $var = array();
    protected static $config = array();

    public static function get($key, $default = null)
    {
        if ( $default !== null ) {
            self::$var[$key] = $default;
        } else {
            if ( !isset(self::$var[$key]) ) {
                self::$var[$key] = null;
            }
        }
        return self::$var[$key];
    }

    public static function config($key)
    {
        $key = explode( '.', $key );
        $value = self::$config;
        while ( $key ) {
            $k = array_shift($key);
            if ( !isset($value[$k]) ) {
                return null;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public static function setConfig($key, $default = null)
    {
        self::$config[$key] = $default;
    }

}