<?php

namespace Negan\Foundation\Http;

use Negan\Foundation\Application;
use Negan\Routing\Router;
use Negan\Routing\Pipeline;
use Negan\Support\Facades\Facade;
use Negan\Foundation\Exceptions\Handler;
use Throwable;

class Kernel
{
    protected $app;
    public $router;
    protected $bootstrappers = [
        \Negan\Foundation\Bootstrap\LoadConfiguration::class,
        \Negan\Foundation\Bootstrap\RegisterFacades::class,
        \Negan\Foundation\Bootstrap\RegisterProviders::class,
        \Negan\Foundation\Bootstrap\BootProviders::class,
    ];
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $routeMiddleware = [];
    protected $middlewarePriority = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        //同步路由中间件给路由器
        $this->syncMiddlewareToRouter();
    }

    /**
     * 将中间件的状态同步到路由器
     *
     * @return void
     */
    protected function syncMiddlewareToRouter()
    {
        $this->router->middlewarePriority = $this->middlewarePriority;

        foreach ($this->middlewareGroups as $key => $middleware) {
            $this->router->middlewareGroup($key, $middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $this->router->aliasMiddleware($key, $middleware);
        }
    }

    public function handle($request)
    {
        try {
            $response = $this->sendRequestThroughRouter($request);
        } catch (Throwable $e) {
            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        Facade::clearResolvedInstance('request');

        $this->bootstrap();

        return (new Pipeline($this->app))
                    ->send($request)
                    ->through($this->middleware)
                    ->then($this->dispatchToRouter());
    }

    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * 获取 路由调度器回调
     * 全局前置中间件最后执行的结果$request会传入 路由器的分派方法里$this->router->dispatch()
     * 路由加载完毕后的结果 可以再返回给全局后置中间件处理
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }

    protected function renderException($request, Throwable $e)
    {
        return $this->app[Handler::class]->render($request, $e);
    }
}