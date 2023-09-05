<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = "", int $code = 403)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
