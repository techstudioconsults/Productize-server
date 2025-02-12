<?php

namespace App\Exceptions;

class ServerErrorException extends ApiException
{
    public function __construct(string $message = 'server error')
    {
        $this->message = $message;
        $this->code = 500;
    }
}
