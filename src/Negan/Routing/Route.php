<?php

namespace Negan\Routing;

use Negan\Http\Request;

class Route {
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    public static $requestUri;
    private static $map = array();

    public static function init()
    {
        self::setUri();
        require_once env( 'BASE_PATH' ) . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        self::goRoute();
    }

    public static function get($uri, $action)
    {
        return self::createRoute('GET', $uri, $action);
    }

    public static function post($uri, $action)
    {
        return self::createRoute('POST', $uri, $action);
    }

    public static function any($uri, $action)
    {
        return self::createRoute(self::$verbs, $uri, $action);
    }

    private static function createRoute($verbs, $uri, $action)
    {
        if ( !$uri || !$action) {
            return false;
        }

        if ( array_key_exists($uri, self::$map) ) {
            return false;
        }

        switch( $action )
        {
            case ( is_object( $action ) && is_a( $action, '\Closure' ) ): //接受闭包函数
                self::$map[$uri] = array(
                    'verbs' => $verbs,
                    'action' => $action
                );
                break;

            case ( is_string( $action ) ): //接受指定控制器方法  e.g  PostController@index    \module\PostController@index
                $action = explode('@', $action);
                if ( count($action) !== 2 ) {
                    return false;
                }
                $path = $action[0];
                $method = $action[1];
                $controllerPath = 'App\Http\Controllers\\' . $path;
                self::$map[$uri] = array(
                    'verbs' => $verbs,
                    'action' => array(
                        'controllerPath' => $controllerPath,
                        'method' => $method
                    )
                );
                break;

            default:
                return false;
                break;
        }
        return true;
    }

    private static function setUri()
    {
        if ( !isset( $_SERVER['REQUEST_URI'] ) || $_SERVER['REQUEST_URI'] == '/' ) {
            return false;
        }
        $protocol = ( ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $uri = parse_url($url, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        self::$requestUri = $uri;
    }

    private static function goRoute()
    {
        if ( array_key_exists(self::$requestUri, self::$map) ) { //uri存在映射方法

            $action = self::$map[self::$requestUri]['action'];
            if ( is_object( $action ) && is_a( $action, '\Closure' ) ) { //调用闭包函数
                $action();
            } else { //调用控制器方法
                $controllerPath = $action['controllerPath'];
                $method = $action['method'];
                if ( !class_exists( $controllerPath ) ) {
                    self::routeNotFound();
                }
                $controller = new $controllerPath();
                if ( !method_exists($controller, $method) ) {
                    self::routeNotFound();
                }
                $request = new Request();
                $controller->$method($request);
            }

        } else {
            self::routeNotFound();
        }
    }

    private static function routeNotFound()
    {
        http_response_code(404);
        exit('Resource not found.');
    }

}
