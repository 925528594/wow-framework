<?php

namespace Negan\Routing;

use Closure;
use Negan\Container\Container;
use Negan\Http\Request;
use Negan\Support\Str;
use Negan\Support\Arr;
use Negan\Support\Traits\Macroable;
use ReflectionFunction;
use RuntimeException;
use Exception;

class Route {
    use Macroable, RouteDependencyResolverTrait;

    public $uri;
    public $methods;
    public $action;
    public $controller;
    public $parameters;
    public $compiled;
    protected $router;
    protected $container;
    protected $bindingFields = [];

    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = (array) $methods;
        $this->action = Arr::except($this->parseAction($action), ['prefix']);

        if ( in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods) ) {
            $this->methods[] = 'HEAD';
        }

        $this->prefix(is_array($action) ? Arr::get($action, 'prefix') : '');
    }

    protected function parseAction($action)
    {
        return RouteAction::parse($this->uri, $action);
    }

    public function bind(Request $request)
    {
        $this->parameters = [];

        return $this;
    }

    public function run()
    {
        $this->container = $this->container ? : new Container;

        try {
            if ($this->isControllerAction()) {
                return $this->runController();
            }

            return $this->runCallable();
        } catch (RuntimeException $e) {
            return $e;
        }
    }

    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this, $this->getController(), $this->getControllerMethod()
        );
    }

    public function controllerDispatcher()
    {
        if ($this->container->bound(ControllerDispatcher::class)) {
            return $this->container->make(ControllerDispatcher::class);
        }

        return new ControllerDispatcher($this->container);
    }

    public function getController()
    {
        if ( !$this->controller ) {
            $class = $this->parseControllerCallback()[0];

            $this->controller = $this->container->make(ltrim($class, '\\'));
        }

        return $this->controller;
    }

    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            $this->parametersWithoutNulls(), new ReflectionFunction($this->action['uses'])
        )));
    }

    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(), function ($p) {
            return ! is_null($p);
        });
    }

    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new Exception('Route is not bound.');
    }

    public function prefix($prefix)
    {
        $this->updatePrefixOnAction($prefix);

        $uri = rtrim($prefix, '/').'/'.ltrim($this->uri, '/');

        return $this->setUri($uri !== '/' ? trim($uri, '/') : $uri);
    }

    protected function updatePrefixOnAction($prefix)
    {
        if (! empty($newPrefix = trim(rtrim($prefix, '/').'/'.ltrim($this->action['prefix'] ?? '', '/'), '/'))) {
            $this->action['prefix'] = $newPrefix;
        }
    }

    public function setUri($uri)
    {
        $this->uri = $this->parseUri($uri);

        return $this;
    }

    public function uri()
    {
        return $this->uri;
    }

    protected function parseUri($uri)
    {
        $this->bindingFields = [];

        return tap(RouteUri::parse($uri), function ($uri) {
            $this->bindingFields = $uri->bindingFields;
        })->uri;
    }

    public function matches(Request $request, $includingMethod = true)
    {
        foreach ($this->getValidators() as $validator) {
            if (! $includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (! $validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }

    public function methods()
    {
        return $this->methods;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    public function setAction(array $action)
    {
        $this->action = $action;

        if (isset($this->action['domain'])) {
            $this->domain($this->action['domain']);
        }

        return $this;
    }

    public function domain($domain = null)
    {
        if (is_null($domain)) {
            return $this->getDomain();
        }

        $parsed = RouteUri::parse($domain);

        $this->action['domain'] = $parsed->uri;

        $this->bindingFields = array_merge(
            $this->bindingFields, $parsed->bindingFields
        );

        return $this;
    }

    public function getAction($key = null)
    {
        return Arr::get($this->action, $key);
    }

    public function getDomain()
    {
        return isset($this->action['domain'])
            ? str_replace(['http://', 'https://'], '', $this->action['domain']) : null;
    }

    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) ($this->action['middleware'] ?? []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) ($this->action['middleware'] ?? []), $middleware
        );

        return $this;
    }

}