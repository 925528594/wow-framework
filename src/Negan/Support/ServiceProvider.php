<?php

namespace Negan\Support;

abstract class ServiceProvider
{
    protected $app;

    /**
     * 创建一个新的服务提供者实例
     *
     * @param \Negan\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 注册任何应用程序的服务
     *
     * @return void
     */
    public function register()
    {
        //
    }

}