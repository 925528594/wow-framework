<?php

namespace Negan\Http;

class ResponseHeaderBag
{

    /**
     * header转换字符串使用
     * @var string
     */
    protected const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    public const COOKIES_FLAT = 'flat';
    public const COOKIES_ARRAY = 'array';

    protected $headers = [];
    protected $cookies = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    public function allPreserveCaseWithoutCookies()
    {
        $headers = $this->headers;
        if (isset($headers['set-cookie'])) {
            unset($headers['set-cookie']);
        }

        foreach ($headers as $key => $value) {
            if ( !is_array($value) ) {
                $headers[$key] = is_string($value) ? [$value] : [json_encode($value)];
            }
        }

        return $headers;
    }

    public function set($key, $values, $replace = true)
    {
        $uniqueKey = strtr($key, self::UPPER, self::LOWER);

        if (is_array($values)) {
            $values = array_values($values);

            if ($replace === true || !isset($this->headers[$key])) {
                $this->headers[$key] = $values;
            } else {
                $this->headers[$key] = array_merge($this->headers[$key], $values);
            }
        } else {
            if ($replace === true || !isset($this->headers[$key])) {
                $this->headers[$key] = [$values];
            } else {
                $this->headers[$key][] = $values;
            }
        }
    }

    public function remove($key)
    {
        $key = strtr($key, self::UPPER, self::LOWER);

        unset($this->headers[$key]);
    }

    public function setCookie($key, $values)
    {
        $this->cookies[$key] = $values;
    }

    public function removeCookie($key)
    {
        unset($this->cookies[$key]);
    }

    public function getCookies($format = self::COOKIES_FLAT)
    {
        if ( !in_array($format, [self::COOKIES_FLAT, self::COOKIES_ARRAY]) ) {
            throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', [self::COOKIES_FLAT, self::COOKIES_ARRAY])));
        }

        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }

        $flattenedCookies = [];
        foreach ($this->cookies as $key => $value) {
            $flattenedCookies[] = $key.'='.$value.'; path=/; samesite=lax';
        }

        return $flattenedCookies;
    }

}
