<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Penalty;
use Library\Entity\User;
use Library\Exception\NotFoundException;
use Library\Repository\PenaltyRepository;

class PenaltyService
{
    use Transactional;

    private PenaltyRepository $penalties;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications
    ) {
        $this->penalties = $em->getRepository(Penalty::class);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function pay(User $actor, int $penaltyId): Penalty
    {
        return $this->transactional(function () use ($actor, $penaltyId): Penalty {
            $penalty = $this->em->find(Penalty::class, $penaltyId, LockMode::PESSIMISTIC_WRITE);
            if ($penalty === null || ! $this->canSee($actor, $penalty)) {
                throw new NotFoundException('Penalty not found.');
            }

            $penalty->markPaid($this->clock->now(), $actor);

            $student = $penalty->getLoan()->getUser();
            $this->audit->log($actor, AuditLogger::PENALTY_PAID, 'penalty', $penalty->getId(), [
                'loanId'       => $penalty->getLoan()->getId(),
                'studentId'    => $student->getId(),
                'amount'       => $penalty->getAmount(),
                'onBehalfOf'   => $actor->isStaff() ? $student->getId() : null,
            ]);
            $this->notifications->markPenaltyAlertsRead($student, $penalty);

            return $penalty;
        });
    }

    public function get(User $actor, int $penaltyId): Penalty
    {
        $penalty = $this->penalties->find($penaltyId);
        if ($penalty === null || ! $this->canSee($actor, $penalty)) {
            throw new NotFoundException('Penalty not found.');
        }

        return $penalty;
    }

    public function search(User $actor, array $filters, int $page, int $limit): array
    {
        if (! $actor->isStaff()) {
            $filters['userId'] = $actor->getId();
        }

        return $this->penalties->search($filters, $page, $limit);
    }

    public function unpaidTotalFor(User $student): string
    {
        return $this->penalties->unpaidTotalForUser($student);
    }

    private function canSee(User $actor, Penalty $penalty): bool
    {
        return $actor->isStaff() || $penalty->getLoan()->getUser()->getId() === $actor->getId();
    }
}
