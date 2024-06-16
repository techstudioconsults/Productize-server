<?php

namespace App\Exceptions;

class TimeOutException extends ApiException
{
    public function __construct(string $message = '', int $code = 408)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
