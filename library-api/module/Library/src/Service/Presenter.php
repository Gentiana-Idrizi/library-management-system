<?php

declare(strict_types=1);

namespace Library\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\Notification;
use Library\Entity\Penalty;
use Library\Entity\SystemLog;
use Library\Entity\User;

class Presenter
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly SettingsService $settings,
        private readonly PenaltyCalculator $calculator
    ) {
    }

    public function user(User $user): array
    {
        return [
            'id'        => $user->getId(),
            'name'      => $user->getName(),
            'email'     => $user->getEmail(),
            'role'      => $user->getRole()->value,
            'createdAt' => self::dateTime($user->getCreatedAt()),
        ];
    }

    public function book(Book $book): array
    {
        return [
            'id'        => $book->getId(),
            'title'     => $book->getTitle(),
            'author'    => $book->getAuthor(),
            'isbn'      => $book->getIsbn(),
            'category'  => $book->getCategory(),
            'stock'     => $book->getStock(),
            'status'    => $book->getStatus(),
            'available' => $book->isAvailable(),
            'updatedAt' => self::dateTime($book->getUpdatedAt()),
        ];
    }

    public function loan(Loan $loan): array
    {
        $today   = $this->clock->today();
        $overdue = $loan->isOverdueOn($today);
        $days    = $overdue ? $this->calculator->daysLate($loan->getDueAt(), $today) : 0;
        $penalty = $loan->getPenalty();
        $book    = $loan->getBook();
        $student = $loan->getUser();

        return [
            'id'          => $loan->getId(),

            'status'      => $overdue ? 'overdue' : $loan->getStatus()->value,
            'borrowedAt'  => self::dateTime($loan->getBorrowedAt()),
            'dueAt'       => $loan->getDueAt()->format('Y-m-d'),
            'returnedAt'  => self::dateTime($loan->getReturnedAt()),
            'borrowFee'   => $loan->getBorrowFee(),
            'daysOverdue' => $days,

            'accruingPenalty' => $overdue
                ? Money::fromCents($this->calculator->amountCents($days, $this->settings->penaltyRateCents()))
                : null,
            'book'    => ['id' => $book->getId(), 'title' => $book->getTitle(), 'author' => $book->getAuthor(), 'isbn' => $book->getIsbn()],
            'student' => ['id' => $student->getId(), 'name' => $student->getName(), 'email' => $student->getEmail()],
            'penalty' => $penalty !== null ? $this->penaltySummary($penalty) : null,
        ];
    }

    public function penalty(Penalty $penalty): array
    {
        $loan = $penalty->getLoan();

        return $this->penaltySummary($penalty) + [
            'loan' => [
                'id'         => $loan->getId(),
                'status'     => $loan->getStatus()->value,
                'dueAt'      => $loan->getDueAt()->format('Y-m-d'),
                'returnedAt' => self::dateTime($loan->getReturnedAt()),
                'book'       => ['id' => $loan->getBook()->getId(), 'title' => $loan->getBook()->getTitle()],
            ],
            'student' => [
                'id'    => $loan->getUser()->getId(),
                'name'  => $loan->getUser()->getName(),
                'email' => $loan->getUser()->getEmail(),
            ],
        ];
    }

    public function notification(Notification $n): array
    {
        return [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'message'   => $n->getMessage(),
            'isRead'    => $n->isRead(),
            'createdAt' => self::dateTime($n->getCreatedAt()),
        ];
    }

    public function log(SystemLog $log): array
    {
        $actor = $log->getActor();

        return [
            'id'         => $log->getId(),
            'actionType' => $log->getActionType(),
            'entityType' => $log->getEntityType(),
            'entityId'   => $log->getEntityId(),
            'timestamp'  => self::dateTime($log->getCreatedAt()),
            'actor'      => $actor !== null
                ? ['id' => $actor->getId(), 'name' => $actor->getName(), 'role' => $actor->getRole()->value]
                : null,
            'details'    => $log->getDetails(),
        ];
    }

    public function page(array $result, callable $map, int $page, int $limit): array
    {
        return [
            'items' => array_map($map, $result['items']),
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    private function penaltySummary(Penalty $penalty): array
    {
        $paidBy = $penalty->getPaidBy();

        return [
            'id'        => $penalty->getId(),
            'amount'    => $penalty->getAmount(),
            'daysLate'  => $penalty->getDaysLate(),
            'paid'      => $penalty->isPaid(),
            'paidAt'    => self::dateTime($penalty->getPaidAt()),
            'paidBy'    => $paidBy !== null ? ['id' => $paidBy->getId(), 'name' => $paidBy->getName()] : null,
            'createdAt' => self::dateTime($penalty->getCreatedAt()),
        ];
    }

    private static function dateTime(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeImmutable::ATOM);
    }
}
