<?php

namespace Negan\Support;

class Env
{
    protected static $var = array();

    public static function get($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        return $value;
    }

    public static function put($key, $value = null)
    {
        if ( is_string($key && is_string($value)) ) {
            $setting = $key . '=' . $value;
            if ( putenv($setting) ) {
                return $value;
            }
        }
        return false;
    }

}