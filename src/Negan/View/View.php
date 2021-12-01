<?php

namespace Negan\View;

use BadMethodCallException;
use Negan\Support\MessageBag;
use Negan\Support\Str;
use Throwable;

class View
{
    protected $factory;
    protected $view;
    protected $data;
    protected $path;

    /**
     * @param string $view
     * @param string $path
     * @param mixed $data
     * @return void
     */
    public function __construct(Factory $factory, $view, $path, $data = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->factory = $factory;

        $this->data = (array) $data;
    }

    public function render(callable $callback = null)
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($contents) : $contents;

            return $response;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    protected function renderContents()
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($this->data);

        try {
            include $this->path;
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleViewException(Throwable $e, $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }

    public function __toString()
    {
        return $this->render();
    }

}
