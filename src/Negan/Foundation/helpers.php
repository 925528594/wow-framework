<?php

use Negan\Container\Container;
use Negan\Routing\ResponseFactory;
use Negan\View\Factory as ViewFactory;

if ( !function_exists('app')) {
    /**
     * 获取 应用实例 或 由应用实例解析$abstract出来的结果
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Negan\Foundation\Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}


if ( !function_exists('config') ) {
    /**
     * 获取指定的配置值
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed|\Negan\Config\Repository
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}


if (! function_exists('base_path')) {
    /**
     * 获取 绝对路径应用根目录(可传路径值)
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return app()->basePath($path);
    }
}


if ( !function_exists('app_path') ) {
    /**
     * 获取 绝对路径应用根目录下的app目录(可传路径值)
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->path($path);
    }
}


if (! function_exists('public_path')) {
    /**
     * 获取 绝对路径应用根目录下的public目录(可传路径值)
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->publicPath($path);
    }
}


if (! function_exists('config_path')) {
    /**
     * 获取 绝对路径应用根目录下的config目录(可传路径值)
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->configPath($path);
    }
}


if (! function_exists('resource_path')) {
    /**
     * 获取 绝对路径应用根目录下的resources目录(可传路径值)
     *
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        return app()->resourcePath($path);
    }
}


if (! function_exists('response')) {
    /**
     * 创建并返回一个响应工厂或响应实例
     *
     * @param \Negan\View\View|string|array|null $content
     * @param int $status
     * @param array $headers
     * @return \Negan\Http\Response|\Negan\Routing\ResponseFactory
     */
    function response($content = '', $status = 200, array $headers = [])
    {
        $factory = app(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $status, $headers);
    }
}


if (! function_exists('view')) {
    /**
     * 创建并返回一个视图实例
     *
     * @param string|null $view
     * @param array $data
     * @param array $mergeData
     * @return \Negan\View\View
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app(ViewFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}