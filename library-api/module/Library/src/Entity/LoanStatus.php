<?php

declare(strict_types=1);

namespace Library\Entity;

enum LoanStatus: string
{
    case Active = 'active';

    case Overdue = 'overdue';

    case AwaitingPayment = 'awaiting_payment';

    case Closed = 'closed';

    public function isOpen(): bool
    {
        return $this === self::Active || $this === self::Overdue;
    }
}
