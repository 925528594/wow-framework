<?php

namespace Negan\Http\Exceptions;

use RuntimeException;
use Negan\Http\Response;

class HttpResponseException extends RuntimeException
{
    protected $response;

    /**
     * @param \Negan\Http\Response $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return \Negan\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
