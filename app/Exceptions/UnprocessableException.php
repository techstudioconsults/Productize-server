<?php

namespace App\Exceptions;

class UnprocessableException extends ApiException
{
    public function __construct(string $message = "", int $code = 422)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
