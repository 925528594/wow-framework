<?php

namespace Negan\Routing;

use Negan\Support\Arr;
use Negan\Support\Reflector;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

trait RouteDependencyResolverTrait
{
    /**
     * @param array $parameters
     * @param object $instance
     * @param string $method
     * @return array
     */
    protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
    {
        if ( !method_exists($instance, $method) ) {
            return $parameters;
        }

        return $this->resolveMethodDependencies(
            $parameters, new ReflectionMethod($instance, $method)
        );
    }

    /**
     * @param array $parameters
     * @param \ReflectionFunctionAbstract $reflector
     * @return array
     */
    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $instanceCount = 0;

        $values = array_values($parameters);

        $skippableValue = new \stdClass;

        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency($parameter, $parameters, $skippableValue);

            if ($instance !== $skippableValue) {
                $instanceCount++;

                $this->spliceIntoParameters($parameters, $key, $instance);
            } elseif (! isset($values[$key - $instanceCount]) &&
                      $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
            }
        }

        return $parameters;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param array $parameters
     * @param object $skippableValue
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters, $skippableValue)
    {
        $className = Reflector::getParameterClassName($parameter);

        if ($className && !$this->alreadyInParameters($className, $parameters)) {
            return $parameter->isDefaultValueAvailable() ? null : $this->container->make($className);
        }

        return $skippableValue;
    }

    /**
     * @param string $class
     * @param array $parameters
     * @return bool
     */
    protected function alreadyInParameters($class, array $parameters)
    {
        return !is_null(Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }

    /**
     * @param array $parameters
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    protected function spliceIntoParameters(array &$parameters, $offset, $value)
    {
        array_splice(
            $parameters, $offset, 0, [$value]
        );
    }
}
