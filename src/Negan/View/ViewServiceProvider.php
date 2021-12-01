<?php

namespace Negan\View;

use Negan\Support\ServiceProvider;
use Negan\View\Factory;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * 引导给定的应用程序
     * 实际就是 注册绑定 视图工厂实例
     *
     * @return void
     */
    public function register()
    {
        $this->registerFactory();
    }

    public function registerFactory()
    {
        $this->app->singleton('view', function ($app) {
            $factory = new Factory($app['config']['view.paths']);

            $factory->setContainer($app);

            return $factory;
        });
    }
}
