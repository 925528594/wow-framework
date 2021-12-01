<?php

namespace Negan\Foundation;

class AliasLoader {
    /**
     * @var array
     */
    protected $aliases = [];
    /**
     * @var bool
     */
    protected $registered = false;
    /**
     * @var \Negan\Foundation\AliasLoader
     */
    protected static $instance;

    public function __construct($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * 获取或创建 别名加载器单例
     *
     * @param array $aliases
     * @return \Negan\Foundation\AliasLoader
     */
    public static function getInstance(array $aliases = [])
    {
        if ( is_null(static::$instance) ) {
            return static::$instance = new static($aliases);
        }

        return static::$instance;
    }

    public function register()
    {
        if ( !$this->registered ) {
            $this->prependToLoaderStack();

            $this->registered = true;
        }
    }

    public function prependToLoaderStack()
    {
        spl_autoload_register([$this, 'load'], true, true);
    }

    public function load($alias)
    {
        if ( isset($this->aliases[$alias]) ) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }
    
}