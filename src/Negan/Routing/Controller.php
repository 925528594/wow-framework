<?php

namespace Negan\Routing;

use BadMethodCallException;

abstract class Controller
{

    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}