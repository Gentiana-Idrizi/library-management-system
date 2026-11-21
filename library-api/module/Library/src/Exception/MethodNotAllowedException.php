<?php

declare(strict_types=1);

namespace Library\Exception;

final class MethodNotAllowedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Method not allowed on this resource.', 'METHOD_NOT_ALLOWED', 405);
    }
}
