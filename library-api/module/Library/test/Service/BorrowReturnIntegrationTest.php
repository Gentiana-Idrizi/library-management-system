<?php

declare(strict_types=1);

namespace LibraryTest\Service;

use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Library\Entity\Book;
use Library\Entity\LoanStatus;
use Library\Entity\Notification;
use Library\Entity\Role;
use Library\Entity\SystemLog;
use Library\Entity\User;
use Library\Exception\BookUnavailableException;
use Library\Exception\BusinessRuleException;
use Library\Service\AuditLogger;
use Library\Service\BorrowPolicy;
use Library\Service\LoanService;
use Library\Service\NotificationService;
use Library\Service\PenaltyCalculator;
use Library\Service\PenaltyService;
use Library\Service\SettingsService;
use LibraryTest\FixedClock;
use PHPUnit\Framework\TestCase;
use Throwable;

final class BorrowReturnIntegrationTest extends TestCase
{
    private EntityManager $em;
    private FixedClock $clock;
    private SettingsService $settings;
    private LoanService $loans;
    private PenaltyService $penalties;
    private User $student;
    private User $operator;

    protected function setUp(): void
    {
        $url = getenv('LIBRARY_TEST_DATABASE_URL') ?: '';
        if ($url === '') {
            self::markTestSkipped('LIBRARY_TEST_DATABASE_URL is not set.');
        }

        try {
            $params     = (new DsnParser(['postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse($url);
            $connection = DriverManager::getConnection($params);
            $connection->executeQuery('SELECT 1');
        } catch (Throwable $e) {
            self::markTestSkipped('Test database not reachable: ' . $e->getMessage());
        }

        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/../../src/Entity']));
        $config->setProxyDir(sys_get_temp_dir() . '/library-test-proxies');
        $config->setProxyNamespace('LibraryTestProxies');
        $config->setAutoGenerateProxyClasses(true);
        $this->em = new EntityManager($connection, $config);

        $tool     = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $this->clock     = new FixedClock(new DateTimeImmutable('2026-03-02 10:00'));
        $audit           = new AuditLogger($this->em, $this->clock);
        $this->settings  = new SettingsService($this->em, $this->clock, $audit);
        $this->settings->ensureDefaults();
        $calculator      = new PenaltyCalculator();
        $notifications   = new NotificationService($this->em, $this->clock, $this->settings, $calculator);
        $this->loans     = new LoanService($this->em, $this->clock, $this->settings, new BorrowPolicy(), $calculator, $audit, $notifications);
        $this->penalties = new PenaltyService($this->em, $this->clock, $audit, $notifications);

        $this->student  = $this->user('Arta', 'arta@test.dev', Role::Student);
        $this->operator = $this->user('Olta', 'olta@test.dev', Role::Operator);
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->getConnection()->close();
        }
    }

    public function testBorrowSetsDueDateChargesFeeAndTakesOneCopy(): void
    {
        $book = $this->book(stock: 5);

        $loan = $this->loans->borrow($this->student, $book->getId());

        self::assertSame('2026-03-16', $loan->getDueAt()->format('Y-m-d'), 'today + 14 days');
        self::assertSame('2.00', $loan->getBorrowFee());
        self::assertSame(LoanStatus::Active, $loan->getStatus());
        self::assertSame(4, $this->reload($book)->getStock());
        self::assertSame(1, $this->countRows(SystemLog::class, ['actionType' => AuditLogger::BOOK_BORROWED]));
    }

    public function testLastCopyThenUnavailableForTheNextStudent(): void
    {
        $book  = $this->book(stock: 1);
        $other = $this->user('Blerim', 'blerim@test.dev', Role::Student);

        $this->loans->borrow($this->student, $book->getId());
        self::assertSame(0, $this->reload($book)->getStock());
        self::assertSame(Book::STATUS_UNAVAILABLE, $this->reload($book)->getStatus());

        try {
            $this->loans->borrow($other, $book->getId());
            self::fail('Expected BOOK_UNAVAILABLE');
        } catch (BookUnavailableException $e) {
            self::assertSame('BOOK_UNAVAILABLE', $e->getErrorCode());
        }
        self::assertSame(0, $this->reload($book)->getStock(), 'stock never goes below zero');

        self::assertSame(1, $this->countRows(Notification::class, ['type' => Notification::TYPE_OUT_OF_STOCK]));
    }

    public function testReturnOnTheExactDueDateHasNoPenalty(): void
    {
        $book = $this->book(stock: 1);
        $loan = $this->loans->borrow($this->student, $book->getId());

        $this->clock->set(new DateTimeImmutable('2026-03-16 23:30'));
        $loan = $this->loans->returnLoan($this->student, $loan->getId());

        self::assertNull($loan->getPenalty());
        self::assertSame(LoanStatus::Closed, $loan->getStatus());
        self::assertSame(1, $this->reload($book)->getStock());
    }

    public function testLateReturnCreatesPenaltyAndRestocksImmediately(): void
    {
        $book = $this->book(stock: 1);
        $loan = $this->loans->borrow($this->student, $book->getId());

        $this->clock->set(new DateTimeImmutable('2026-03-19 09:00'));
        $loan = $this->loans->returnLoan($this->operator, $loan->getId());

        self::assertNotNull($loan->getPenalty());
        self::assertSame(3, $loan->getPenalty()->getDaysLate());
        self::assertSame('3.00', $loan->getPenalty()->getAmount(), '3 days x €1.00');
        self::assertSame(LoanStatus::AwaitingPayment, $loan->getStatus(), 'loan stays open until paid');
        self::assertSame(1, $this->reload($book)->getStock(), 'book is back on the shelf before payment');
    }

    public function testUnpaidPenaltyBlocksBorrowingUntilPaid(): void
    {
        $first  = $this->book(stock: 1);
        $second = $this->book(stock: 1, isbn: '9780135957059');
        $loan   = $this->loans->borrow($this->student, $first->getId());

        $this->clock->set(new DateTimeImmutable('2026-03-17 09:00'));
        $loan = $this->loans->returnLoan($this->student, $loan->getId());

        try {
            $this->loans->borrow($this->student, $second->getId());
            self::fail('Expected UNPAID_PENALTY');
        } catch (BusinessRuleException $e) {
            self::assertSame('UNPAID_PENALTY', $e->getErrorCode());
        }

        $penalty = $this->penalties->pay($this->operator, $loan->getPenalty()->getId());
        self::assertTrue($penalty->isPaid());
        self::assertSame(LoanStatus::Closed, $penalty->getLoan()->getStatus());
        self::assertSame($this->operator->getId(), $penalty->getPaidBy()->getId());

        $this->loans->borrow($this->student, $second->getId());
        $this->addToAssertionCount(1);
    }

    public function testBorrowLimitFromSettings(): void
    {
        $this->settings->update($this->user('Admin', 'admin@test.dev', Role::SuperAdmin), ['borrowLimit' => 2]);
        $a = $this->book(stock: 3, isbn: '9780000000001');
        $b = $this->book(stock: 3, isbn: '9780000000002');
        $c = $this->book(stock: 3, isbn: '9780000000003');

        $this->loans->borrow($this->student, $a->getId());
        $this->loans->borrow($this->student, $b->getId());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('at most 2 books');
        $this->loans->borrow($this->student, $c->getId());
    }

    public function testSettingChangesApplyToNewLoans(): void
    {
        $admin = $this->user('Admin', 'admin@test.dev', Role::SuperAdmin);
        $this->settings->update($admin, ['borrowFee' => '3.50', 'borrowPeriodDays' => 7, 'penaltyRate' => '0.50']);

        $loan = $this->loans->borrow($this->student, $this->book(stock: 1)->getId());
        self::assertSame('3.50', $loan->getBorrowFee());
        self::assertSame('2026-03-09', $loan->getDueAt()->format('Y-m-d'));

        $this->clock->set(new DateTimeImmutable('2026-03-13'));
        $loan = $this->loans->returnLoan($this->student, $loan->getId());
        self::assertSame('2.00', $loan->getPenalty()->getAmount(), '4 days x €0.50');
    }

    public function testStudentCannotReturnSomeoneElsesLoan(): void
    {
        $loan  = $this->loans->borrow($this->student, $this->book(stock: 1)->getId());
        $other = $this->user('Blerim', 'blerim@test.dev', Role::Student);

        $this->expectExceptionMessage('Loan not found');
        $this->loans->returnLoan($other, $loan->getId());
    }

    private function user(string $name, string $email, Role $role): User
    {
        $user = new User($name, $email, password_hash('secret123', PASSWORD_BCRYPT, ['cost' => 4]), $role, $this->clock->now());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function book(int $stock, string $isbn = '9780132350884'): Book
    {
        $book = new Book('Book ' . $isbn, 'Author', $isbn, 'Test', $stock, $this->clock->now());
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    private function reload(Book $book): Book
    {
        $this->em->refresh($book);

        return $book;
    }

    private function countRows(string $entity, array $criteria): int
    {
        return $this->em->getRepository($entity)->count($criteria);
    }
}
