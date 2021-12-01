<?php

namespace Negan\Routing;

use Negan\Support\Arr;
use Negan\Http\Request;
use Negan\Http\Exceptions\NotFoundHttpException;

class RouteCollection
{
    protected $routes = [];
    protected $allRoutes = [];
    protected $actionList = [];

    public function getRoutes()
    {
        return array_values($this->allRoutes);
    }

    public function add(Route $route)
    {
        $this->addToCollections($route);

        $this->addLookups($route);

        return $route;
    }

    /**
     * @param \Negan\Routing\Route $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain().$route->uri();

        foreach ($route->methods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method.$domainAndUri] = $route;
    }

    /**
     * @param \Negan\Routing\Route $route
     * @return void
     */
    protected function addLookups($route)
    {
        $action = $route->getAction();

        if ( isset($action['controller']) ) {
            $this->addToActionList($action, $route);
        }
    }

    protected function addToActionList($action, $route)
    {
        $this->actionList[trim($action['controller'], '\\')] = $route;
    }

    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        $route = $this->matchAgainstRoutes($routes, $request);

        return $this->handleMatchedRoute($request, $route);
    }

    public function get($method = null)
    {
        return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
    }

    protected function matchAgainstRoutes(array $routes, $request)
    {
        $requestUri = $request->getRequestUri();

        foreach ($routes as $k => $route) {
            if ($requestUri === trim($route->uri(), '/')) {
                return $route;
            }
        }

        return null;
    }

    protected function handleMatchedRoute(Request $request, $route)
    {
        if ( !is_null($route) ) {
            return $route->bind($request);
        }

        throw new NotFoundHttpException;
    }
}