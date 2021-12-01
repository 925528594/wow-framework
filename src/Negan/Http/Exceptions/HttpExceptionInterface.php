<?php

namespace Negan\Http\Exceptions;

interface HttpExceptionInterface extends \Throwable
{
    public function getStatusCode();

    public function getHeaders();
}
