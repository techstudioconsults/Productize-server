<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Forbidden')
    {
        $this->message = $message;
        $this->code = 403;
    }
}
