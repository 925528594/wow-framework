<?php

namespace Negan\Foundation\Bootstrap;

use Negan\Foundation\Application;

class BootProviders
{
    /**
     * 引导给定的应用程序
     * 实际就是 启动应用程序, 执行 所有已注册服务提供者 的boot()方法
     * 
     * @param \Negan\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}
