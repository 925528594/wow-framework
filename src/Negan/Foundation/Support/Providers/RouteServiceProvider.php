<?php

namespace Negan\Foundation\Support\Providers;

use Negan\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace;

    public function boot()
    {
        $this->loadRoutes();

        $this->app->booted(function () {
            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        });

    }

    protected function loadRoutes()
    {
        if (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }
    
}
