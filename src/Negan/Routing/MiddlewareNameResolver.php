<?php

namespace Negan\Routing;

use Closure;

class MiddlewareNameResolver
{
    /**
     * @param \Closure|string $name
     * @param array $map
     * @param array $middlewareGroups
     * @return \Closure|string|array
     */
    public static function resolve($name, $map, $middlewareGroups)
    {
        if ($name instanceof Closure) {
            return $name;
        }

        if (isset($map[$name]) && $map[$name] instanceof Closure) {
            return $map[$name];
        }

        if (isset($middlewareGroups[$name])) {
            return static::parseMiddlewareGroup($name, $map, $middlewareGroups);
        }

        [$name, $parameters] = array_pad(explode(':', $name, 2), 2, null);

        return ($map[$name] ?? $name).(! is_null($parameters) ? ':'.$parameters : '');
    }

    /**
     * @param string $name
     * @param array $map
     * @param array $middlewareGroups
     * @return array
     */
    protected static function parseMiddlewareGroup($name, $map, $middlewareGroups)
    {
        $results = [];

        foreach ($middlewareGroups[$name] as $middleware) {
            if (isset($middlewareGroups[$middleware])) {
                $results = array_merge($results, static::parseMiddlewareGroup(
                    $middleware, $map, $middlewareGroups
                ));

                continue;
            }

            [$middleware, $parameters] = array_pad(
                explode(':', $middleware, 2), 2, null
            );

            if (isset($map[$middleware])) {
                $middleware = $map[$middleware];
            }

            $results[] = $middleware.($parameters ? ':'.$parameters : '');
        }

        return $results;
    }
}
