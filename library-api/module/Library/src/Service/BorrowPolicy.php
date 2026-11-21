<?php

declare(strict_types=1);

namespace Library\Service;

use Library\Exception\BookUnavailableException;
use Library\Exception\BusinessRuleException;

final class BorrowPolicy
{
    public function assertCanBorrow(int $unpaidPenalties, int $openLoans, int $borrowLimit, int $stock, string $bookTitle): void
    {
        if ($unpaidPenalties > 0) {
            throw new BusinessRuleException(
                'UNPAID_PENALTY',
                'You have unpaid penalties. Please pay them before borrowing another book.',
                ['unpaidPenalties' => $unpaidPenalties]
            );
        }

        if ($openLoans >= $borrowLimit) {
            throw new BusinessRuleException(
                'BORROW_LIMIT_REACHED',
                sprintf('You can have at most %d books borrowed at the same time.', $borrowLimit),
                ['limit' => $borrowLimit, 'current' => $openLoans]
            );
        }

        if ($stock <= 0) {
            throw new BookUnavailableException($bookTitle);
        }
    }
}
