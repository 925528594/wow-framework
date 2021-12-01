<?php

namespace Negan\Support\Facades;

/**
 * Class Request
 * @see \Negan\Http\Request
 */
class Request extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'request';
    }
}