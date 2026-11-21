<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\Penalty;
use Library\Entity\Role;
use Library\Entity\User;
use Library\Exception\ForbiddenException;
use Library\Exception\NotFoundException;
use Library\Repository\LoanRepository;
use Library\Repository\PenaltyRepository;

class LoanService
{
    use Transactional;

    private LoanRepository $loans;
    private PenaltyRepository $penalties;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly SettingsService $settings,
        private readonly BorrowPolicy $policy,
        private readonly PenaltyCalculator $calculator,
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications
    ) {
        $this->loans     = $em->getRepository(Loan::class);
        $this->penalties = $em->getRepository(Penalty::class);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function borrow(User $actor, int $bookId): Loan
    {
        if ($actor->getRole() !== Role::Student) {
            throw new ForbiddenException('Only students can borrow books.');
        }

        return $this->transactional(function () use ($actor, $bookId): Loan {
            $today = $this->clock->today();

            $student = $this->em->find(User::class, $actor->getId(), LockMode::PESSIMISTIC_WRITE);

            $book = $this->em->find(Book::class, $bookId, LockMode::PESSIMISTIC_WRITE);
            if ($book === null || $book->isDeleted()) {
                throw new NotFoundException('Book not found.');
            }

            $this->policy->assertCanBorrow(
                $this->penalties->countUnpaidByUser($student),
                $this->loans->countOpenByUser($student),
                $this->settings->borrowLimit(),
                $book->getStock(),
                $book->getTitle()
            );

            $book->checkOut();

            $dueAt = $today->modify(sprintf('+%d days', $this->settings->borrowPeriodDays()));
            $fee   = Money::fromCents($this->settings->borrowFeeCents());
            $loan  = new Loan($student, $book, $this->clock->now(), $dueAt, $fee);
            $this->em->persist($loan);
            $this->em->flush();

            $this->audit->log($student, AuditLogger::BOOK_BORROWED, 'loan', $loan->getId(), [
                'bookId'      => $book->getId(),
                'title'       => $book->getTitle(),
                'dueAt'       => $dueAt->format('Y-m-d'),
                'borrowFee'   => $fee,
                'stockAfter'  => $book->getStock(),
            ]);
            $this->notifications->notifyStaffAboutStock($book);

            return $loan;
        });
    }

    public function returnLoan(User $actor, int $loanId): Loan
    {
        return $this->transactional(function () use ($actor, $loanId): Loan {
            $this->em->getConnection()->executeQuery('SELECT id FROM loans WHERE id = ? FOR UPDATE', [$loanId]);

            $loan = $this->em->find(Loan::class, $loanId);
            if ($loan === null || (! $actor->isStaff() && $loan->getUser()->getId() !== $actor->getId())) {
                throw new NotFoundException('Loan not found.');
            }

            $book = $this->em->find(Book::class, $loan->getBook()->getId(), LockMode::PESSIMISTIC_WRITE);

            $now      = $this->clock->now();
            $daysLate = $this->calculator->daysLate($loan->getDueAt(), $now);
            $penalty  = null;
            if ($daysLate > 0) {
                $amount  = $this->calculator->amountCents($daysLate, $this->settings->penaltyRateCents());
                $penalty = new Penalty($loan, Money::fromCents($amount), $daysLate, $now);
            }

            $loan->markReturned($now, $penalty);
            $book->checkIn();
            if ($penalty !== null) {
                $this->em->persist($penalty);
            }
            $this->em->flush();

            $this->audit->log($actor, AuditLogger::BOOK_RETURNED, 'loan', $loan->getId(), [
                'bookId'     => $book->getId(),
                'title'      => $book->getTitle(),
                'studentId'  => $loan->getUser()->getId(),
                'daysLate'   => $daysLate,
                'stockAfter' => $book->getStock(),
            ]);

            if ($penalty !== null) {
                $this->audit->log($actor, AuditLogger::PENALTY_ISSUED, 'penalty', $penalty->getId(), [
                    'loanId'    => $loan->getId(),
                    'studentId' => $loan->getUser()->getId(),
                    'daysLate'  => $daysLate,
                    'amount'    => $penalty->getAmount(),
                ]);
                $this->notifications->notifyPenaltyIssued($penalty);
            }

            return $loan;
        });
    }

    public function get(User $actor, int $loanId): Loan
    {
        $loan = $this->loans->find($loanId);
        if ($loan === null || (! $actor->isStaff() && $loan->getUser()->getId() !== $actor->getId())) {
            throw new NotFoundException('Loan not found.');
        }
        $this->refreshOverdue();

        return $loan;
    }

    public function search(User $actor, array $filters, int $page, int $limit): array
    {
        if (! $actor->isStaff()) {
            $filters['userId'] = $actor->getId();
        }
        $this->refreshOverdue();

        return $this->loans->search($filters, $page, $limit);
    }

    public function refreshOverdue(): void
    {
        $this->loans->markOverdue($this->clock->today());
    }
}
