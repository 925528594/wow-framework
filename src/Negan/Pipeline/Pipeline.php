<?php

namespace Negan\Pipeline;

use Closure;
use Negan\Container\Container;
use RuntimeException;
use Throwable;

class Pipeline {
    protected $container;
    protected $passable;
    protected $pipes = [];
    protected $method = 'handle';

    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * 设置通过管道要传入的对象$passable
     *
     * @param mixed $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * 设置管道数组
     *
     * @param array|mixed $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * 设置调用管道的方法
     *
     * @param string $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 为管道配置 最终回调函数 并运行管道
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * 运行管道并返回结果
     *
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    /**
     * 获取已配置管道的数组
     *
     * @return array
     */
    protected function pipes()
    {
        return $this->pipes;
    }

    /**
     * 获取一个为array_reduce()做迭代的回调函数
     * 中间件handle()方法里的$next($request) 就是 function($passable) use ($stack, $pipe)(){}
     * 中间件handle()方法里若缺失了$next($request)管道便会中断(Laravel会抛异常), 所以中间件的写法很严格
     * 迭代会把$passable传递到下一个回调函数中, 所以$passable会贯穿所有的中间件, 并且都是同一个
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function($stack, $pipe){
            return function($passable) use ($stack, $pipe) {
                try {
                    if ( is_callable($pipe) ) {
                        return $pipe($passable, $stack);
                    } elseif ( !is_object($pipe) ) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        $pipe = $this->getContainer()->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                                    ? $pipe->{$this->method}(...$parameters)
                                    : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * 解析传入的管道字符串以获取$name和$parameters
     *
     * @param string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * 获取服务容器实例
     *
     * @return \Negan\Container\Container
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * 获取最后的回调
     * 执行到最后一个中间件handle()方法里的$next($request) 就是 function($passable) use ($destination) {}
     *
     * @param \Closure $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        // 执行完所有中间件后便会执行这个最终的回调函数
        return function($passable) use ($destination) {
            try {
                // 直接调用回调函数$destination(), 这个回调函数就是管道类$Pipeline->then()里传入的参数$destination
                // 并且会往$destination回调函数里传入一个参数$passable, 这个$passable在所有的中间件都有出现, 并且都是同一个$passable
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    /**
     * 处理每个管道中即将传递到下一管道的$carry值
     *
     * @param  mixed $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * 处理给定的异常
     *
     * @param mixed $passable
     * @param \Throwable $e
     * @return mixed
     * @throws \Throwable
     */
    protected function handleException($passable, Throwable $e)
    {
        throw $e;
    }


}