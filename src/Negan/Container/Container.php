<?php

namespace Negan\Container;

use Closure;
use Exception;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use LogicException;
use ReflectionException;

class Container implements ArrayAccess
{
    /**
     * @var static 用静态变量保存该容器的实例
     */
    protected static $instance;

    protected $instances = [];

    protected $bindings = [];

    protected $aliases = [];

    protected $abstractAliases = [];

    protected $with = [];
    
    /**
     * $abstract 绑定共享实例
     *
     * @param string $abstract 特定名称
     * @param mixed $instance 实例对象|字符串|...
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        // 清除别名绑定, 避免在容器$container->make()时读取到$container->aliases[]别名绑定的特定名称而无法返回共享实例绑定的值
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * $abstract 绑定 别名
     * ------ 绑定至
     * $container->aliases[别名] = $abstract
     * $container->abstractAliases[$abstract][] = 别名
     * ------
     * $container->aliases[]可以给$abstract取多个别名, 甚至允许 a 是 b 别名，b 是 c 的别名, 原因是$container->getAlias()为一个递归函数
     *
     * @see \Negan\Container\Container::getAlias()
     * @param $abstract 任何值
     * @param $alias 别名
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * 别名 获取 $abstract
     * ----- 获取从
     * $container->aliases[别名] = $abstract
     * -----
     *
     * @param string $abstract 类名|别名|特定名称
     * @return mixed 原值返回|$abstract
     */
    public function getAlias($abstract)
    {
        if ( !isset($this->aliases[$abstract]) ) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * 判断别名是否有绑定$abstract
     *
     * @param $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * $abstract 绑定 $container->bindings[]数组
     * $shared参数传入true,  在服务容器调用make()方法来实例化$abstract时, 会生成实例化对象绑定到$container->instances[]数组(单例模式)
     *
     * @param string $abstract 特定名称
     * @param \Closure|string|null $concrete 闭包函数|可构建类名|空
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * $abstract 绑定至 $container->bindings[]数组
     * $shared参数默认为false, 在服务容器调用make()方法时, 生成的实例对象不会绑定到$container->instances[]数组, 相反则绑定
     * $concrete视为可构建类名时, 会调用容器$container->getClosure()方法来生成闭包函数, 并绑定$container->bindings[]数组, 绑定格式如下
     * $container->bindings[$abstract] = [闭包函数, $shared]
     *
     * @param $abstract 特定名称
     * @param \Closure|string|null $concrete 闭包函数|可构建类名|字符串
     * @param bool $shared 是否做单例绑定
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        // $concrete为空时, 将$abstract视为可构建类名, 并赋值给$concrete
        if ( is_null($concrete) ) {
            $concrete = $abstract;
        }

        // $concrete不为闭包函数时, 将$concrete视为可构建类名, 并调用容器$this->getClosure()方法来生成闭包函数
        if ( !$concrete instanceof Closure ) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * 清理 共享实例绑定 和 别名绑定
     *
     * @param $abstract 特定名称
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * 生成闭包函数并返回
     *
     * @param string $abstract 特定名称
     * @param string $concrete 可构建类名
     * @return \Closure 闭包函数
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * 解析 类名|别名|特定名称
     *
     * @param string $abstract 类名|别名|特定名称
     * @param array $parameters
     * @return mixed instances[$abstract]的值 | bindings[$abstract]['concrete']闭包函数的执行结果 | 反射类构建出来的实例对象
     * @throws ReflectionException
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * 解析 类名|别名|特定名称
     *
     * @param string $abstract 类名|别名|特定名称
     * @param array $parameters
     * @return mixed instances[$abstract]的值 | bindings[$abstract]['concrete']闭包函数的执行结果 | 反射类构建出来的实例对象
     * @throws \Throwable
     */
    protected function resolve($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        //1. 判断共享单例中是否存在该值, 有则返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        //2. 判断容器中$this->bindings是否有绑定闭包函数, 有的话返回闭包函数, 没有返回原值
        $concrete = $this->getConcrete($abstract);

        //3. 执行闭包函数或构建实例
        //   闭包会被直接执行返回
        //   原值会被当做"陌生的类名"做反射类实例化返回
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            //判断条件似乎永远都是true, 但Laravel源码就是这么写，目前未知此处代码块的作用
            $object = $this->make($concrete);
        }

        //4. 绑定共享实例
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        array_pop($this->with);

        return $object;
    }

    /**
     * 尝试获取$container->bindings[$abstract]['concrete']闭包函数, 若没有返回原值
     *
     * @param string $abstract 类名|别名|特定名称
     * @return mixed 原值返回 | bindings[$abstract]['concrete']闭包函数
     */
    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * 判断是否可以构建实例对象
     * 满足以下任一条件均可构建实例对象
     * 1. $concrete === $abstract  如果成立, 意味着在容器中无任何绑定对象, 视为可以实例化的一个类名, 由build方法执行(可能异常)
     * 2. $concrete instanceof Closure 如果成立, 可视为从$container->bindings获取的闭包函数, 由build方式执行
     *
     * @param mixed $concrete 闭包函数|可构建类名
     * @param string $abstract 闭包函数|可构建类名
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * 构建实例对象
     * 1. 若$concrete为闭包函数则执行函数并返回
     * 2. 否则$concrete将当作类名且通过反射类构建该类并返回, 构建过程中实现类构造方法的依赖注入
     *
     * @param \Closure|string $concrete 闭包函数|可构建类名
     * @return mixed 闭包函数执行结果|实例对象
     * @throws \Throwable
     */
    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ReflectionException("Target class [$concrete] does not exist.", 0, $e);
        }

        if ( !$reflector->isInstantiable() ) {
            throw new ReflectionException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ( is_null($constructor) ) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (Exception $e) {
            throw $e;
        }

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * 判断是否有参数覆盖
     *
     * @param  \ReflectionParameter  $dependency
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * 获取覆盖参数传入的值
     *
     * @param \ReflectionParameter $dependency
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * 构建实例对象时, 获取之前服务容器make()方法传入的覆盖参数
     *
     * @return array 传入参数数组|空数组
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * 解析反射类参数
     * 该方法由build方法执行, 通过反射类的方法取出所有传入的参数, 然后通过此方法解析所有参数并返回
     * 1. 若参数声明类型非类, 则获取值
     * 2. 若参数声明类型为类, 则调用make方法反射实例类
     *
     * @param \ReflectionParameter[] $dependencies 反射类参数
     * @return array 参数值
     * @throws \Throwable
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            $results[] = is_null(Util::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * 反射类参数获取值
     *
     * @param \ReflectionParameter $parameter
     * @return mixed
     * @throws \ReflectionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        $message = "Unresolvable parameter resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        throw new Exception($message);
    }

    /**
     * 反射类参数类型为类, 通过make方法实例化
     *
     * @param \ReflectionParameter $parameter
     * @return mixed
     * @throws \ReflectionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        } catch ( Exception $e ) {
            if ( $parameter->isOptional() ) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * 获取$container->bindings[$abstract]['shared']布尔值
     * $container->make()方法根据该值决定实例对象是否绑定到服务容器$container->instances
     * 
     * @param string $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public static function setInstance(Container $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * 数组式访问
     * 详见接口ArrayAccess: 提供像访问数组一样访问对象的能力的接口
     * offsetExists() offsetGet() offsetSet() offsetUnset()
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key]);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            isset($this->aliases[$abstract]);
    }


    public function call($callback, array $parameters = [])
    {
        return call_user_func_array($callback, $parameters);
    }

}