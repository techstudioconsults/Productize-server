<?php

namespace App\Exceptions;

class ConflictException extends ApiException
{
    public function __construct(string $message = '', int $code = 409)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
