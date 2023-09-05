<?php

namespace App\Exceptions;

class ServerErrorException extends ApiException
{
    public function __construct(string $message = "", int $code = 500)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
