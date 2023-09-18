<?php

namespace App\Exceptions;

class TooManyRequestException extends ApiException
{
    public function __construct(string $message = "Too Many Request")
    {
        $this->message = $message;
        $this->code = 429;
    }
}
