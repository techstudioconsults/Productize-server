<?php

namespace App\Exceptions;

class UnprocessableException extends ApiException
{
    public function __construct(string $message = '')
    {
        $this->message = $message;
        $this->code = 422;
    }
}
