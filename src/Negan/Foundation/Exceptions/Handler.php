<?php

namespace Negan\Foundation\Exceptions;

use Negan\Http\Exceptions\HttpException;
use Negan\Http\Exceptions\HttpResponseException;
use Negan\Http\Response;
use Negan\Routing\Router;
use Negan\Support\Arr;
use Exception;
use Throwable;

class Handler
{
    /**
     * @param \Negan\Http\Request $request
     * @param \Throwable $e
     * @return \Negan\Http\Response
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        return $this->prepareResponse($request, $e);
    }

    /**
     * @param \Negan\Http\Request $request
     * @param \Throwable $e
     * @return \Negan\Http\Response
     */
    protected function prepareResponse($request, Throwable $e)
    {
        if ( !$this->isHttpException($e) ) {
            $e = new HttpException(500, $e->getMessage());
        }

        return $this->convertExceptionToResponse($e);
    }

    /**
     * @param \Throwable $e
     * @return \Negan\Http\Response
     */
    protected function convertExceptionToResponse(Throwable $e)
    {
        $response = new Response(
            config('app.debug') ? $e->getMessage() : '',
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : []
        );

        return $response->withException($e);
    }

    /**
     * @param \Throwable $e
     * @return bool
     */
    protected function isHttpException(Throwable $e)
    {
        return $e instanceof HttpException;
    }

}
