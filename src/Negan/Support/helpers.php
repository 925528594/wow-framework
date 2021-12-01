<?php

use Negan\Support\Env;
use Negan\Foundation\Config;
use Negan\Support\HigherOrderTapProxy;

if ( !function_exists('env') ) {
    /**
     * 获取环境变量的值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        return Env::get($key, $default);
    }
}


if (! function_exists('value')) {
    /**
     * 返回给定值的默认值
     * 给定值为闭包函数时会执行并返回, 否则返回原值
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}


if (! function_exists('tap')) {
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

