<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\Notification;
use Library\Entity\Penalty;
use Library\Entity\User;
use Library\Exception\NotFoundException;
use Library\Repository\LoanRepository;
use Library\Repository\NotificationRepository;
use Library\Repository\PenaltyRepository;
use Library\Repository\UserRepository;

class NotificationService
{
    private NotificationRepository $notifications;
    private LoanRepository $loans;
    private PenaltyRepository $penalties;
    private UserRepository $users;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly SettingsService $settings,
        private readonly PenaltyCalculator $calculator
    ) {
        $this->notifications = $em->getRepository(Notification::class);
        $this->loans         = $em->getRepository(Loan::class);
        $this->penalties     = $em->getRepository(Penalty::class);
        $this->users         = $em->getRepository(User::class);
    }

    public function notifyStaffAboutStock(Book $book): void
    {
        $stock     = $book->getStock();
        $threshold = $this->settings->lowStockThreshold();

        if ($stock === 0) {
            $type    = Notification::TYPE_OUT_OF_STOCK;
            $message = sprintf('Out of stock: "%s" (ISBN %s) has no copies left.', $book->getTitle(), $book->getIsbn());
        } elseif ($stock <= $threshold) {
            $type    = Notification::TYPE_LOW_STOCK;
            $message = sprintf(
                'Low stock: "%s" (ISBN %s) has only %d %s left.',
                $book->getTitle(),
                $book->getIsbn(),
                $stock,
                $stock === 1 ? 'copy' : 'copies'
            );
        } else {
            return;
        }

        foreach ($this->users->findActiveStaff() as $staff) {
            $this->em->persist(new Notification($staff, $type, $message, $this->clock->now()));
        }
    }

    public function notifyPenaltyIssued(Penalty $penalty): void
    {
        $loan = $penalty->getLoan();
        $this->em->persist(new Notification(
            $loan->getUser(),
            Notification::TYPE_PENDING_PENALTY,
            sprintf(
                'Penalty of €%s for returning "%s" %d day(s) late. Please pay it to close the loan.',
                $penalty->getAmount(),
                $loan->getBook()->getTitle(),
                $penalty->getDaysLate()
            ),
            $this->clock->now(),
            'penalty:' . $penalty->getId()
        ));
    }

    public function syncStudentAlerts(User $student): void
    {
        $today    = $this->clock->today();
        $existing = array_flip($this->notifications->existingDedupeKeys($student));

        foreach ($this->loans->findOverdueByUser($student, $today) as $loan) {
            $loan->markOverdue();
            $key = 'overdue:loan:' . $loan->getId();
            if (isset($existing[$key])) {
                continue;
            }
            $days = $this->calculator->daysLate($loan->getDueAt(), $today);
            $this->em->persist(new Notification(
                $student,
                Notification::TYPE_OVERDUE,
                sprintf(
                    '"%s" was due on %s and is %d day(s) overdue. Penalty so far: €%s (grows by €%s per day).',
                    $loan->getBook()->getTitle(),
                    $loan->getDueAt()->format('Y-m-d'),
                    $days,
                    Money::fromCents($this->calculator->amountCents($days, $this->settings->penaltyRateCents())),
                    Money::fromCents($this->settings->penaltyRateCents())
                ),
                $this->clock->now(),
                $key
            ));
        }

        foreach ($this->penalties->findUnpaidByUser($student) as $penalty) {
            $key = 'penalty:' . $penalty->getId();
            if (isset($existing[$key])) {
                continue;
            }
            $this->em->persist(new Notification(
                $student,
                Notification::TYPE_PENDING_PENALTY,
                sprintf(
                    'Reminder: you have an unpaid penalty of €%s for "%s". You cannot borrow books until it is paid.',
                    $penalty->getAmount(),
                    $penalty->getLoan()->getBook()->getTitle()
                ),
                $this->clock->now(),
                $key
            ));
        }

        $this->em->flush();
    }

    public function listFor(User $user, bool $unreadOnly, int $limit = 50): array
    {
        if (! $user->isStaff()) {
            $this->syncStudentAlerts($user);
        }

        return [
            'items'       => $this->notifications->findForUser($user, $unreadOnly, $limit),
            'unreadCount' => $this->notifications->countUnread($user),
        ];
    }

    public function markRead(User $user, int $id): Notification
    {
        $notification = $this->notifications->find($id);
        if ($notification === null || $notification->getUser()->getId() !== $user->getId()) {
            throw new NotFoundException('Notification not found.');
        }
        $notification->markRead();
        $this->em->flush();

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllRead($user);
    }

    public function markPenaltyAlertsRead(User $student, Penalty $penalty): void
    {
        $this->notifications->markReadByDedupeKey($student, 'penalty:' . $penalty->getId());
    }
}
