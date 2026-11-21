<?php

declare(strict_types=1);

namespace Library\Exception;

final class ValidationException extends ApiException
{
    public function __construct(array $errors, string $message = 'The given data is invalid.')
    {
        parent::__construct($message, 'VALIDATION_FAILED', 422, ['fields' => $errors]);
    }
}
