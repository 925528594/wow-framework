<?php

namespace Negan\View;

use Negan\Container\Container;
use InvalidArgumentException;

class Factory
{
    protected $container;
    protected $paths;
    protected $views = [];
    protected $extensions = ['blade.php', 'php', 'css', 'html'];

    public function __construct(array $paths, array $extensions = null)
    {
        $this->paths = array_map([$this, 'resolvePath'], $paths);

        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    protected function resolvePath($path)
    {
        return realpath($path) ?: $path;
    }

    public function make($view, $data = [])
    {
        $path = $this->find($view);

        return new View($this, $view, $path, $data);
    }

    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if (file_exists($viewPath = $path.'/'.$file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', '/', $name).'.'.$extension;
        }, $this->extensions);
    }


}