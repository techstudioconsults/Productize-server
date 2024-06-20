<?php

namespace App\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $message = 'Bad Request')
    {
        $this->message = $message;
        $this->code = 400;
    }
}
