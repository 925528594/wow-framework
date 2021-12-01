<?php

namespace Negan\Support\Facades;

/**
 * @method static \Negan\Routing\Route any(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route delete(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route get(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route match(array|string $methods, string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route options(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route patch(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route post(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\Route put(string $uri, array|string|callable|null $action = null)
 * @method static \Negan\Routing\RouteRegistrar middleware(array|string|null $middleware)
 * @method static \Negan\Routing\RouteRegistrar namespace(string $value)
 * @method static \Negan\Routing\RouteRegistrar prefix(string  $prefix)
 * @method static \Negan\Routing\Router|\Negan\Routing\RouteRegistrar group(\Closure|string|array $attributes, \Closure|string $routes)
 *
 * Class Route
 * @see \Negan\Routing\Route
 */
class Route extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}