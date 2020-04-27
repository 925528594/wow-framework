<?php

namespace Negan\Support;

class Env
{
    protected static $var = array();

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
}