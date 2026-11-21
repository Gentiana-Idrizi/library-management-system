<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Exception\BusinessRuleException;
use Library\Repository\LoanRepository;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loans')]
#[ORM\Index(name: 'idx_loans_user_status', columns: ['user_id', 'status'])]
#[ORM\Index(name: 'idx_loans_status_due', columns: ['status', 'due_at'])]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(name: 'book_id', nullable: false)]
    private Book $book;

    #[ORM\Column(name: 'borrowed_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $borrowedAt;

    #[ORM\Column(name: 'due_at', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(name: 'returned_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $returnedAt = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: LoanStatus::class)]
    private LoanStatus $status;

    #[ORM\Column(name: 'borrow_fee', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $borrowFee;

    #[ORM\OneToOne(targetEntity: Penalty::class, mappedBy: 'loan')]
    private ?Penalty $penalty = null;

    public function __construct(
        User $user,
        Book $book,
        DateTimeImmutable $borrowedAt,
        DateTimeImmutable $dueAt,
        string $borrowFee
    ) {
        $this->user       = $user;
        $this->book       = $book;
        $this->borrowedAt = $borrowedAt;
        $this->dueAt      = $dueAt;
        $this->borrowFee  = $borrowFee;
        $this->status     = LoanStatus::Active;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isOverdueOn(DateTimeImmutable $today): bool
    {
        return $this->isOpen() && $today->format('Y-m-d') > $this->dueAt->format('Y-m-d');
    }

    public function markOverdue(): void
    {
        if ($this->status === LoanStatus::Active) {
            $this->status = LoanStatus::Overdue;
        }
    }

    public function markReturned(DateTimeImmutable $returnedAt, ?Penalty $penalty): void
    {
        if (! $this->isOpen()) {
            throw new BusinessRuleException('LOAN_ALREADY_RETURNED', 'This book has already been returned.');
        }
        $this->returnedAt = $returnedAt;
        $this->penalty    = $penalty;
        $this->status     = $penalty !== null ? LoanStatus::AwaitingPayment : LoanStatus::Closed;
    }

    public function close(): void
    {
        if ($this->status !== LoanStatus::AwaitingPayment) {
            throw new BusinessRuleException('LOAN_NOT_AWAITING_PAYMENT', 'This loan is not awaiting a payment.');
        }
        $this->status = LoanStatus::Closed;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getBorrowedAt(): DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function getDueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getReturnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function getStatus(): LoanStatus
    {
        return $this->status;
    }

    public function getBorrowFee(): string
    {
        return $this->borrowFee;
    }

    public function getPenalty(): ?Penalty
    {
        return $this->penalty;
    }
}
