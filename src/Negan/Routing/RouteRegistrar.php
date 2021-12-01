<?php

namespace Negan\Routing;

use Closure;
use Negan\Support\Arr;
use Negan\Support\Reflector;
use BadMethodCallException;
use InvalidArgumentException;

class RouteRegistrar
{
    protected $router;
    protected $attributes = [];
    protected $passthru = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any',
    ];
    protected $allowedAttributes = [
        'middleware', 'namespace', 'prefix',
    ];
    protected $aliases = [
        'name' => 'as',
    ];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function attribute($key, $value)
    {
        if ( !in_array($key, $this->allowedAttributes) ) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }
        
        $this->attributes[Arr::get($this->aliases, $key, $key)] = $value;

        return $this;
    }
    
    public function match($methods, $uri, $action = null)
    {
        return $this->router->match($methods, $uri, $this->compileAction($action));
    }

    public function group($callback)
    {
        $this->router->group($this->attributes, $callback);
    }

    public function __call($method, $parameters)
    {
        if ( in_array($method, $this->passthru) ) {
            return $this->registerRoute($method, ...$parameters);
        }

        if ( in_array($method, $this->allowedAttributes) ) {
            if ( $method === 'middleware' ) {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, $parameters[0]);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    protected function registerRoute($method, $uri, $action = null)
    {
        if ( !is_array($action) ) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }

        return $this->router->{$method}($uri, $this->compileAction($action));
    }

    protected function compileAction($action)
    {
        if ( is_null($action) ) {
            return $this->attributes;
        }

        if ( is_string($action) || $action instanceof Closure ) {
            $action = ['uses' => $action];
        }

        if ( is_array($action) &&
            !Arr::isAssoc($action) &&
            Reflector::isCallable($action)) {
            $action = [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        }

        return array_merge($this->attributes, $action);
    }

}