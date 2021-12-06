<?php

namespace Negan\Routing;

use Negan\Support\ServiceProvider;
use Negan\View\Factory;

class RoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRouter();
        $this->registerRedirector();
        $this->registerResponseFactory();
    }

    public function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app);
        });
    }

    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            return new Redirector();
        });
    }

    protected function registerResponseFactory()
    {
        $this->app->singleton(ResponseFactory::class, function ($app) {
            return new ResponseFactory($app[Factory::class], $app['redirect']);
        });
    }

}