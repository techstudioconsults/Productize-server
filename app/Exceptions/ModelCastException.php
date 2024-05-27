<?php

namespace App\Exceptions;

class ModelCastException extends ApiException
{
    public function __construct(string $expectedModel, string $receivedModel)
    {
        $message = "Invalid model. Expected instance of {$expectedModel}, but received instance of {$receivedModel}";
        parent::__construct($message, 500);
    }
}
