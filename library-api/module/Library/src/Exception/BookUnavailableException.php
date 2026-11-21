<?php

declare(strict_types=1);

namespace Library\Exception;

final class BookUnavailableException extends BusinessRuleException
{
    public function __construct(string $title)
    {
        parent::__construct(
            'BOOK_UNAVAILABLE',
            sprintf('"%s" is currently unavailable: all copies are borrowed.', $title),
            ['status' => 'unavailable']
        );
    }
}
