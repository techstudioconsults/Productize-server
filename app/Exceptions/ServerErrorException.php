<?php

namespace App\Exceptions;

class ServerErrorException extends ApiException
{
    public function __construct(string $message = "")
    {
        $this->message = $message;
        $this->code = 500;
    }
}
