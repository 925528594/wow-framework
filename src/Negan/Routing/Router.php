<?php

namespace Negan\Routing;

use Negan\Container\Container;
use Closure;
use Negan\Http\Request;
use Negan\Http\Response;
use Negan\Support\Traits\Macroable;

class Router {
    use Macroable {
        __call as macroCall;
    }
    public $container;
    protected $routes;
    protected $current;
    protected $currentRequest;
    public    $middlewarePriority = [];
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $groupStack = [];
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public function __construct(Container $container = null)
    {
        $this->routes = new RouteCollection();
        $this->container = $container ? : new Container();
    }

    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    public function get($uri, $action = null)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    public function post($uri, $action = null)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action = null)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action = null)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action = null)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function options($uri, $action = null)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    public function any($uri, $action = null)
    {
        return $this->addRoute(self::$verbs, $uri, $action);
    }

    public function match($methods, $uri, $action = null)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    public function addRoute($methods, $uri, $action)
    {
        return $this->routes->add($this->createRoute($methods, $uri, $action));
    }

    protected function createRoute($methods, $uri, $action)
    {
        if ( $this->actionReferencesController($action) ) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute(
            $methods, $this->prefix($uri), $action
        );

        if ( $this->hasGroupStack() ) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        return $route;
    }

    protected function actionReferencesController($action)
    {
        if ( !$action instanceof Closure ) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    protected function convertToControllerAction($action)
    {
        if ( is_string($action) ) {
            $action = ['uses' => $action];
        }

        if ( $this->hasGroupStack() ) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        $action['controller'] = $action['uses'];

        return $action;
    }

    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
            ? $group['namespace'].'\\'.$class : $class;
    }

    protected function prefix($uri)
    {
        return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
    }

    public function getLastGroupPrefix()
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['prefix'] ?? '';
        }

        return '';
    }

    public function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action));
//            ->setRouter($this)
//            ->setContainer($this->container);
    }

    /**
     * @param \Negan\Routing\Route $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $route->setAction($this->mergeWithLastGroup(
            $route->getAction(),
            $prependExistingPrefix = false
        ));
    }

    public function group(array $attributes, $routes)
    {
        $this->updateGroupStack($attributes);

        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    protected function updateGroupStack(array $attributes)
    {
        if ( $this->hasGroupStack() ) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    public function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    public function mergeWithLastGroup($new, $prependExistingPrefix = true)
    {
        return RouteGroup::merge($new, end($this->groupStack), $prependExistingPrefix);
    }

    protected function loadRoutes($routes)
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            require $routes;
        }
    }

    /**
     * 将请求分派到应用程序
     *
     * @param \Negan\Http\Request $request
     * @return \Negan\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request);
    }

    public function dispatchToRoute(Request $request)
    {
        return $this->runRoute($request, $this->findRoute($request));
    }

    protected function runRoute(Request $request, Route $route)
    {
        return $this->prepareResponse($request,
            $this->runRouteWithinStack($route, $request)
        );
    }

    protected function findRoute(Request $request)
    {
        $this->current = $route = $this->routes->match($request);

        $this->container->instance(Route::class, $route);

        return $route;
    }

    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $middleware = $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->container))
                        ->send($request)
                        ->through($middleware)
                        ->then(function ($request) use ($route) {
                            return $this->prepareResponse(
                                $request, $route->run()
                            );
                        });
    }

    public function gatherRouteMiddleware(Route $route)
    {
        $middleware = [];

        $gatherMiddleware = $route->middleware();

        foreach ($gatherMiddleware as $name){
            $resolvedMiddleware = (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
            array_walk($resolvedMiddleware, function (&$value, $key) use (&$middleware){
                $middleware[] = $value;
            });
        }

        return $middleware;
    }

    public function prepareResponse($request, $response)
    {
        return static::toResponse($request, $response);
    }

    public static function toResponse($request, $response)
    {
        if ( !$response instanceof Response ) {
            $response = new Response($response, 200, ['Content-Type' => 'text/html']);
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        return $response;
    }


    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ($method === 'middleware') {
            return (new RouteRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        return (new RouteRegistrar($this))->attribute($method, $parameters[0]);
    }

}
