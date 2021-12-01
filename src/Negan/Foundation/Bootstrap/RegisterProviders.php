<?php

namespace Negan\Foundation\Bootstrap;

use Negan\Foundation\Application;

class RegisterProviders
{
    /**
     * 引导给定的应用程序
     * 实际就是 注册 配置项里(项目/config/app.php 里的providers) 所有的服务提供者
     *
     * @param \Negan\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->registerConfiguredProviders();
    }
}
