<?php

namespace App\Exceptions;

class FunnelDeployException extends ServerErrorException
{
    public function __construct(string $message = '')
    {
        $this->message = $message;
    }
}
