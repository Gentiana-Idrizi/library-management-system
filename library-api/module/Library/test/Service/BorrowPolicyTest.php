<?php

declare(strict_types=1);

namespace LibraryTest\Service;

use Library\Exception\BookUnavailableException;
use Library\Exception\BusinessRuleException;
use Library\Service\BorrowPolicy;
use PHPUnit\Framework\TestCase;

final class BorrowPolicyTest extends TestCase
{
    private function context(
        int $unpaid = 0,
        int $open = 0,
        int $limit = 3,
        int $stock = 5
    ): array {
        return [$unpaid, $open, $limit, $stock, 'Clean Code'];
    }

    private function assertDenied(string $code, array $context): void
    {
        try {
            (new BorrowPolicy())->assertCanBorrow(...$context);
            self::fail("Expected $code");
        } catch (BusinessRuleException $e) {
            self::assertSame($code, $e->getErrorCode());
            self::assertSame(409, $e->getHttpStatus());
        }
    }

    public function testAllowsANormalBorrow(): void
    {
        (new BorrowPolicy())->assertCanBorrow(...$this->context());
        $this->addToAssertionCount(1);
    }

    public function testLastCopyCanStillBeBorrowed(): void
    {
        (new BorrowPolicy())->assertCanBorrow(...$this->context(stock: 1));
        $this->addToAssertionCount(1);
    }

    public function testOutOfStockGivesAClearUnavailableAnswer(): void
    {
        $this->expectException(BookUnavailableException::class);
        $this->expectExceptionMessage('unavailable');
        (new BorrowPolicy())->assertCanBorrow(...$this->context(stock: 0));
    }

    public function testAnyUnpaidPenaltyBlocksBorrowing(): void
    {
        $this->assertDenied('UNPAID_PENALTY', $this->context(unpaid: 1));
    }

    public function testUnpaidPenaltyIsReportedEvenIfTheBookIsAlsoOutOfStock(): void
    {
        $this->assertDenied('UNPAID_PENALTY', $this->context(unpaid: 1, stock: 0));
    }

    public function testBorrowLimitIsEnforced(): void
    {
        (new BorrowPolicy())->assertCanBorrow(...$this->context(open: 2, limit: 3));
        $this->assertDenied('BORROW_LIMIT_REACHED', $this->context(open: 3, limit: 3));
    }
}
