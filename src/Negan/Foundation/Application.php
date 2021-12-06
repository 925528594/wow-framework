<?php

namespace Negan\Foundation;

use Negan\Container\Container;
use Negan\Support\ServiceProvider;
use Negan\Routing\RoutingServiceProvider;

class Application extends Container
{
    /**
     * 源码借鉴Laravel Framework 7.30.4
     */
    const VERSION = '1.0.0';
    protected $basePath;
    protected $appPath;
    protected $booted = false;
    protected $hasBeenBootstrapped = false;
    protected $serviceProviders = [];

    public function __construct($basePath = null)
    {
        if ( $basePath ) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * 注册基本共享实例绑定
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);
    }

    /**
     * 注册服务提供者
     * 该注册方法只注册部分服务提供者而不执行boot启动方法
     * 服务提供者: 服务提供者给予框架开启多种多样的组件, 像数据库, 队列, 验证器, 以及路由组件. 只要被启动服务提供者就可支配框架的所有功能.
     *   知识点: 在服务容器创建完后, 会交给内核类会完成所有服务提供者的注册与启动
     *          @see \Negan\Foundation\Http\Kernel::sendRequestThroughRouter 方法中调用$application->bootstrap().
     */
    protected function registerBaseServiceProviders()
    {
        $this->register((new RoutingServiceProvider($this)));
    }

    /**
     * 注册核心类别名
     * 核心类名称(特定名称) 绑定 别名
     * 可以通过$container->getAlias()方法解析出 核心类名称(特定名称)
     * 理解重点: 比如 'router' => [\Negan\Routing\Router::class]
     *              router 等于 核心类名称($abstract特定名称)
     *              \Negan\Routing\Router::class 等于 别名
     *          绑定后容器中的格式为
     *              $container->aliases['\Negan\Routing\Router::class'] = 'router';
     *              $container->abstractAliases['router'][] = '\Negan\Routing\Router::class';
     */
    protected function registerCoreContainerAliases()
    {
        foreach ([
                     'app'  => [self::class],
                     'config' => [\Negan\Config\Repository::class],
                     'request' => [\Negan\Http\Request::class],
                     'redirect' => [\Negan\Routing\Redirector::class],
                     'router' => [\Negan\Routing\Router::class],
                     'view' => [\Negan\View\Factory::class],
                 ] as $abstract => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($abstract, $alias);
            }
        }
    }

    /**
     * 注册应用的服务提供者
     *
     * @param \Negan\Support\ServiceProvider|string $provider
     * @return \Negan\Support\ServiceProvider
     */
    public function register($provider)
    {
        $provider->register();

        if ( property_exists($provider, 'bindings') ) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if ( property_exists($provider, 'singletons') ) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function isBooted()
    {
        return $this->booted;
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        if ( method_exists($provider, 'boot') ) {
            return $this->call([$provider, 'boot']);
        }
    }

    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
    }

    /**
     * 从容器中解析 类名|别名|特定名称
     * Laravel单例模式, 若已有共享实例则返回共享实例(通过$abstract从容器的$container->instances获取共享实例)
     *   知识点: 假设要在服务容器中引用use \xxx\yyy; 可以先在服务容器中做基础绑定, 后通过$container->make()方法获取实例
     *          容器中两个基础绑定方法$container->singleton(), $container->bind()都是绑定在容器中$container->bindings数组里
     *          此后通过$container->make()方法获取实例时的差别：
     *              1. 经过$container->singleton()绑定的, 第一次获取会构建好实例对象并绑定在容器共享实例$container->instances里且返回
     *                 此后获取的都是从容器共享实例$container->instances里拿的, 即单例模式
     *              2. 经过$container->bind()绑定的, 每次获取都要构建实例对象返回, 没有绑定共享实例
     *
     * @param string $abstract 类名|别名|特定名称
     * @param array $parameters
     * @return mixed 实例对象|字符串|闭包函数执行结果
     * @throws \ReflectionException
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        return parent::make($abstract, $parameters);
    }

    /**
     * @@@ 未深入待完善
     * 从容器中解析 类名|别名|特定名称
     * 与make方法功能一样, 在Laravel源码中传入参数多一个$raiseEvents
     * 
     * @param string $abstract 类名|别名|接口名
     * @param array $parameters
     * @return mixed 实例对象|字符串
     * @throws \ReflectionException
     */
    protected function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        return parent::resolve($abstract, $parameters);
    }

    /**
     * @@@ 未深入待完善
     *
     * @param string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return parent::bound($abstract);
    }


    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    /**
     * 注册所有已配置的提供程序
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $providers = $this->config['app.providers'];

        foreach ($providers as $key => $provider) {
            $this->register(new $provider($this));
        }
    }

    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }

        array_walk($this->serviceProviders, function ($provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;
    }

    public function booted($callback)
    {
        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($this);
        }
    }

    protected function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->instance('path.base', $this->basePath());           // 绝对路径应用根目录
        $this->instance('path', $this->path());                    // 绝对路径应用根目录下的app目录
        $this->instance('path.public', $this->publicPath());       // 绝对路径应用根目录下的public目录
        $this->instance('path.config', $this->configPath());       // 绝对路径应用根目录下的config目录
        $this->instance('path.resources', $this->resourcePath());  // 绝对路径应用根目录下的resources目录
        $this->instance('path.bootstrap', $this->bootstrapPath()); // 绝对路径应用根目录下的bootstrap目录
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function path($path = '')
    {
        $appPath = $this->appPath ?: $this->basePath.DIRECTORY_SEPARATOR.'app';

        return $appPath . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function publicPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'public' . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function configPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config' . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function resourcePath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'resources' . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function bootstrapPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap' . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }



}
