<?php

namespace Negan\Routing;

class RouteUri
{
    public $uri;
    public $bindingFields = [];

    public function __construct(string $uri, array $bindingFields = [])
    {
        $this->uri = $uri;
        $this->bindingFields = $bindingFields;
    }

    public static function parse($uri)
    {
        preg_match_all('/\{([\w\:]+?)\??\}/', $uri, $matches);

        $bindingFields = [];

        foreach ($matches[0] as $match) {
            if (strpos($match, ':') === false) {
                continue;
            }

            $segments = explode(':', trim($match, '{}?'));

            $bindingFields[$segments[0]] = $segments[1];

            $uri = strpos($match, '?') !== false
                    ? str_replace($match, '{'.$segments[0].'?}', $uri)
                    : str_replace($match, '{'.$segments[0].'}', $uri);
        }

        return new static($uri, $bindingFields);
    }
}
