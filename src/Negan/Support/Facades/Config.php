<?php

namespace Negan\Support\Facades;

/**
 * Class Config
 * @see \Negan\Config\Repository
 */
class Config extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}