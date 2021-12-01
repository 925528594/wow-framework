<?php

namespace Negan\Http;

class Request
{
    public $query = [];
    public $request = [];
    public $server = [];
    public $headers = [];
    public $cookies = [];
    protected $content = [];
    protected $method;
    protected $requestUrl;
    protected $requestUri;

    public function __construct()
    {
        $this->initializeRequest();
    }

    protected function initializeRequest()
    {
        //初始化 GET参数
        $this->loadGet();

        //初始化 POST参数 请求原始数据的只读流
        $this->loadPostContent();

        //初始化 server headers cookies url uri method
        $this->loadServer();
    }

    protected function loadGet()
    {
        $queryUrl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];;
        $queryParam  = parse_url($queryUrl, PHP_URL_QUERY);
        $queryParam  = html_entity_decode($queryParam);
        $queryParam  = explode('&', $queryParam);
        foreach($queryParam as $value)
        {
            $i = explode('=', $value);
            $this->query[$i[0]] = isset($i[1]) ? $i[1] : '';
        }
    }

    protected function loadPostContent()
    {
        $this->content = file_get_contents("php://input");

        $this->request = array_merge($this->query, $this->jsonToArray($this->content), $_POST);
    }

    protected function loadServer()
    {
        // server
        $this->server = $_SERVER;

        // headers
        foreach ($this->server as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $this->headers[$name] = $value;
            } else if ($name == "CONTENT_TYPE") {
                $this->headers["Content-Type"] = $value;
            } else if ($name == "CONTENT_LENGTH") {
                $this->headers["Content-Length"] = $value;
            }
        }

        // cookies
        $this->cookies = $_COOKIE;

        // url uri
        if (array_key_exists('REQUEST_URI', $this->server)) {
            $protocol = ((!empty( $this->server['HTTPS']) && $this->server['HTTPS'] != 'off') || $this->server['SERVER_PORT'] == 443)
                ? "https://"
                : "http://";
            $url = $protocol . $this->server['HTTP_HOST'] . $this->server['REQUEST_URI'];
            $uri = trim(parse_url($url, PHP_URL_PATH), '/');
            $this->requestUrl = $url;
            $this->requestUri = $uri;
        }

        // method
        if (array_key_exists('REQUEST_METHOD', $this->server)) {
            $this->method = $this->server['REQUEST_METHOD'];
        }

    }

    public static function capture()
    {
        return new static();
    }

    public function has($key)
    {
        if (array_key_exists($key, $this->request)) {
            return true;
        }

        return false;
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        if(array_key_exists($key, $this->query)) {
            $value = $this->query[$key];
        } else {
            $value = $default;
        }

        return $this->removeXss($value);
    }

    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }

        if (array_key_exists($key, $this->request)) {
            $value = $this->request[$key];
        } else {
            $value = $default;
        }

        return $this->removeXss($value);
    }

    public function hasHeader($key)
    {
        return array_key_exists($key, $this->headers);
    }

    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }

        if (array_key_exists($key, $this->headers)) {
            $value = $this->headers[$key];
        } else {
            $value = $default;
        }

        return $this->removeXss($value);
    }

    public function hasCookie($key)
    {
        return array_key_exists($key, $this->cookies);
    }

    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        if (array_key_exists($key, $this->cookies)) {
            $value = $this->cookies[$key];
        } else {
            $value = $default;
        }

        return $this->removeXss($value);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = '';
        }

        return $this->requestUri;
    }

    public function removeXss($val)
    {
        if (!is_string($val)) {
            return $val;
        }
        $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); ++$i) {
            $val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link',
            'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer',
            'layer', 'bgsound', 'title', 'base');

        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut',
            'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur',
            'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable',
            'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave',
            'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin',
            'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown',
            'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend',
            'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart',
            'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);
        $found = true;
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); ++$i) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); ++$j) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
                        $pattern .= '|(&#0{0,8}([9][10][13]);?)?';
                        $pattern .= ')?';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    $found = false;
                }
            }
        }
        return $val;
    }

    protected function jsonToArray($json) {
        $i = json_decode(trim($json), true);
        if (!is_array($i)) {
            return [];
        }
        return $i;
    }

}