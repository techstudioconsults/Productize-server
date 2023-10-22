<?php

namespace App\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $message = "")
    {
        $this->message = $message;
        $this->code = 400;
    }
}
