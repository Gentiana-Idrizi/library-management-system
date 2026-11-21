<?php

declare(strict_types=1);

namespace LibraryTest\Entity;

use DateTimeImmutable;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\LoanStatus;
use Library\Entity\Penalty;
use Library\Entity\Role;
use Library\Entity\User;
use Library\Exception\BookUnavailableException;
use Library\Exception\BusinessRuleException;
use PHPUnit\Framework\TestCase;

final class BookAndLoanTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-03-01 10:00');
    }

    public function testStockDrivesStatus(): void
    {
        $book = new Book('Clean Code', 'Robert C. Martin', '9780132350884', 'SE', 1, $this->now);
        self::assertSame(Book::STATUS_AVAILABLE, $book->getStatus());

        $book->checkOut();
        self::assertSame(0, $book->getStock());
        self::assertSame(Book::STATUS_UNAVAILABLE, $book->getStatus());

        $book->checkIn();
        self::assertSame(Book::STATUS_AVAILABLE, $book->getStatus());
    }

    public function testCannotCheckOutWhenStockIsZero(): void
    {
        $book = new Book('Clean Code', 'Robert C. Martin', '9780132350884', 'SE', 0, $this->now);

        $this->expectException(BookUnavailableException::class);
        $book->checkOut();
    }

    public function testOnTimeReturnClosesTheLoan(): void
    {
        $loan = $this->loan();
        $loan->markReturned(new DateTimeImmutable('2026-03-15 18:00'), null);

        self::assertSame(LoanStatus::Closed, $loan->getStatus());
    }

    public function testLateReturnWaitsForPaymentThenCloses(): void
    {
        $loan    = $this->loan();
        $penalty = new Penalty($loan, '3.00', 3, $this->now);
        $loan->markReturned(new DateTimeImmutable('2026-03-18'), $penalty);
        self::assertSame(LoanStatus::AwaitingPayment, $loan->getStatus());

        $penalty->markPaid(new DateTimeImmutable('2026-03-18 12:00'), $loan->getUser());
        self::assertTrue($penalty->isPaid());
        self::assertSame(LoanStatus::Closed, $loan->getStatus());
    }

    public function testCannotReturnTwiceOrPayTwice(): void
    {
        $loan    = $this->loan();
        $penalty = new Penalty($loan, '1.00', 1, $this->now);
        $loan->markReturned(new DateTimeImmutable('2026-03-16'), $penalty);
        $penalty->markPaid($this->now, $loan->getUser());

        try {
            $loan->markReturned(new DateTimeImmutable('2026-03-17'), null);
            self::fail('Second return should fail');
        } catch (BusinessRuleException $e) {
            self::assertSame('LOAN_ALREADY_RETURNED', $e->getErrorCode());
        }

        $this->expectExceptionMessage('already been paid');
        $penalty->markPaid($this->now, $loan->getUser());
    }

    public function testOverdueStartsTheDayAfterTheDueDate(): void
    {
        $loan = $this->loan();
        self::assertFalse($loan->isOverdueOn(new DateTimeImmutable('2026-03-15')));
        self::assertTrue($loan->isOverdueOn(new DateTimeImmutable('2026-03-16')));
    }

    private function loan(): Loan
    {
        $user = new User('Arta', 'arta@example.com', 'hash', Role::Student, $this->now);
        $book = new Book('Clean Code', 'Robert C. Martin', '9780132350884', 'SE', 1, $this->now);

        return new Loan($user, $book, $this->now, new DateTimeImmutable('2026-03-15'), '2.00');
    }
}
