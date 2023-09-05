<?php

namespace App\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $message = "", int $code = 400)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
