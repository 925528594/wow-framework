<?php

namespace Negan\Support\Traits;

use BadMethodCallException;
use Closure;

trait Macroable
{
    protected static $macros = [];

    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    public static function __callStatic($method, $parameters)
    {
        if ( !static::hasMacro($method) ) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ( $macro instanceof Closure ) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if ( !static::hasMacro($method) ) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ( $macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }

}