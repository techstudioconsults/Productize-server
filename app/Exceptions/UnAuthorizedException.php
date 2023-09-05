<?php

namespace App\Exceptions;

class UnAuthorizedException extends ApiException
{
    public function __construct(string $message = "", int $code = 401)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
