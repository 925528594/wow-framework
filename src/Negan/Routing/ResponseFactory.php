<?php

namespace Negan\Routing;

use Negan\Http\JsonResponse;
use Negan\Http\Response;
use Negan\View\Factory;
use Negan\Support\Str;
use Negan\Support\Traits\Macroable;

class ResponseFactory
{
    use Macroable;

    protected $view;

    /**
     * @param \Negan\View\Factory $view
     * @return void
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return \Negan\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    /**
     * @param int $status
     * @param array $headers
     * @return \Negan\Http\Response
     */
    public function noContent($status = 204, array $headers = [])
    {
        return $this->make('', $status, $headers);
    }

    /**
     * @param string|array $view
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return \Negan\Http\Response
     */
    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        return $this->make($this->view->make($view, $data), $status, $headers);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return \Negan\Http\JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }
}
