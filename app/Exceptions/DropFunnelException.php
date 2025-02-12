<?php

namespace App\Exceptions;

class DropFunnelException extends ServerErrorException
{
    public function __construct(string $message = '')
    {
        $this->message = $message;
    }
}
