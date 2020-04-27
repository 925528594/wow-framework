<?php

namespace Negan\Http;

class Request
{
    protected $dataGet;
    protected $dataPost;
    protected $dataInput;

    public function __construct()
    {
        //GET
        $queryUrl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];;
        $queryParam  = parse_url( $queryUrl, PHP_URL_QUERY );
        $queryParam  = html_entity_decode( $queryParam );
        $queryParam  = explode('&', $queryParam);
        foreach($queryParam as $value)
        {
            $i          = explode('=', $value);
            $this->dataGet[$i[0]] = isset($i[1]) ? $i[1] : '';
        }
        unset($queryParam, $value, $i);

        //POST
        $this->dataPost = $_POST;

        //请求的原始数据的只读流
        $phpInput = file_get_contents("php://input");
        $this->dataInput = $this->jsonToArray($phpInput);
        unset($phpInput);
    }

    public function jsonToArray($json) {
        $i = json_decode( trim( $json ), true );
        if ( !is_array($i) ) {
            return array();
        }
        return $i;
    }


    public function get(string $key, $default = null)
    {
        $value = array_key_exists( $key, $this->dataGet ) ? $this->dataGet[$key] : $default;
        return $this->removeXss($value);
    }

    public function input(string $key, $default = null)
    {
        $value = array_key_exists( $key, $this->dataInput ) ? $this->dataInput[$key] : ( array_key_exists( $key, $this->dataPost ) ? $this->dataPost[$key] : (array_key_exists( $key, $this->dataGet ) ? $this->dataGet[$key] : $default ) );
        return $this->removeXss($value);
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

}