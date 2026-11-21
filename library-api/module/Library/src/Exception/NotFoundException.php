<?php

declare(strict_types=1);

namespace Library\Exception;

final class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message, 'NOT_FOUND', 404);
    }
}
