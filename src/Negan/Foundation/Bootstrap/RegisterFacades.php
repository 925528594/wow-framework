<?php

namespace Negan\Foundation\Bootstrap;

use Negan\Foundation\AliasLoader;
use Negan\Foundation\Application;
use Negan\Support\Facades\Facade;

class RegisterFacades
{
    /**
     * 引导给定的应用程序
     * 实际就是 注册 配置项里(项目/config/app.php 里的aliases) 做门面加载的自动加载器
     * 说明: 假设A::method(), 在调用某不存在的类名A时, php会自动加载spl_autoload_register预先定义好的回调函数[$this, 'load'],
     *      $AliasLoader->load()回调方法返回在别名数组中已绑定的门面类\facadeFullPath\A::class,
     *      调用门面类\facadeFullPath\A::class不存在的静态方法A::method()时又会触发门面类的魔术方法__callStatic(),
     *      然后门面类的魔术方法可以通过容器的解析方法$app->make()构建出A的实例并调用他的方法A::method()
     *
     * @param \Negan\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        Facade::clearResolvedInstances();
        
        Facade::setFacadeApplication($app);

        AliasLoader::getInstance($app->make('config')->get('app.aliases', []))->register();
    }
}