<?php

namespace Negan\Container;

use Closure;
use ReflectionNamedType;

class Util
{

    /**
     * 反射类参数 获取 类名
     *
     * @param  \ReflectionParameter  $parameter
     * @return string|null
     */
    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if ( !$type instanceof ReflectionNamedType || $type->isBuiltin() ) {
            return;
        }

        $name = $type->getName();

        if ( !is_null($class = $parameter->getDeclaringClass()) ) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}
