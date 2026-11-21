<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Exception\BusinessRuleException;
use Library\Repository\PenaltyRepository;

#[ORM\Entity(repositoryClass: PenaltyRepository::class)]
#[ORM\Table(name: 'penalties')]
#[ORM\Index(name: 'idx_penalties_paid', columns: ['paid'])]
class Penalty
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Loan::class, inversedBy: 'penalty')]
    #[ORM\JoinColumn(name: 'loan_id', nullable: false, unique: true)]
    private Loan $loan;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(name: 'days_late', type: Types::INTEGER)]
    private int $daysLate;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $paid = false;

    #[ORM\Column(name: 'paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'paid_by_id', nullable: true)]
    private ?User $paidBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(Loan $loan, string $amount, int $daysLate, DateTimeImmutable $createdAt)
    {
        $this->loan      = $loan;
        $this->amount    = $amount;
        $this->daysLate  = $daysLate;
        $this->createdAt = $createdAt;
    }

    public function markPaid(DateTimeImmutable $paidAt, User $paidBy): void
    {
        if ($this->paid) {
            throw new BusinessRuleException('PENALTY_ALREADY_PAID', 'This penalty has already been paid.');
        }
        $this->paid   = true;
        $this->paidAt = $paidAt;
        $this->paidBy = $paidBy;
        $this->loan->close();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDaysLate(): int
    {
        return $this->daysLate;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getPaidBy(): ?User
    {
        return $this->paidBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
