<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiException extends Exception
{
    public function __construct(string $message = '', ?int $code = null)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function render()
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'success' => false,
        ], $this->getCode());
    }
}
