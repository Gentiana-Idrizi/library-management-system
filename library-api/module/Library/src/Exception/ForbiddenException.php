<?php

declare(strict_types=1);

namespace Library\Exception;

final class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'You do not have permission to do this.')
    {
        parent::__construct($message, 'FORBIDDEN', 403);
    }
}
