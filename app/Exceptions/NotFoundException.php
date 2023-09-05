<?php

namespace App\Exceptions;

class NotFoundException extends ApiException
{
    public function __construct(string $message = "", int $code = 404)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
