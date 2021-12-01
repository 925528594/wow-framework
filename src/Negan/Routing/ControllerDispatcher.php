<?php

namespace Negan\Routing;

use Negan\Container\Container;

class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

    protected $container;

    /**
     * @param \Negan\Container\Container $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \Negan\Routing\Route $route
     * @param mixed $controller
     * @param string $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }
    
}
