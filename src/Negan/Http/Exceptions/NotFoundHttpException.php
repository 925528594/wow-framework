<?php

namespace Negan\Http\Exceptions;

class NotFoundHttpException extends HttpException
{
    public function __construct(?string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        if ($message == '') {
            $message = '404 NOT FOUND!';
        }

        parent::__construct(404, $message, $previous, $headers, $code);
    }
}
