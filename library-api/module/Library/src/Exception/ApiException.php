<?php

declare(strict_types=1);

namespace Library\Exception;

use RuntimeException;

abstract class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly int $httpStatus,
        private readonly array $details = []
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
