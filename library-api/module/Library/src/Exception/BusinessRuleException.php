<?php

declare(strict_types=1);

namespace Library\Exception;

class BusinessRuleException extends ApiException
{
    public function __construct(string $errorCode, string $message, array $details = [])
    {
        parent::__construct($message, $errorCode, 409, $details);
    }
}
