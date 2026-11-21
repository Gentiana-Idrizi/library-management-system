<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Loan;
use Library\Entity\Penalty;
use Library\Entity\Role;
use Library\Entity\User;
use Library\Exception\BusinessRuleException;
use Library\Exception\ForbiddenException;
use Library\Exception\NotFoundException;
use Library\Repository\LoanRepository;
use Library\Repository\PenaltyRepository;
use Library\Repository\UserRepository;
use Library\Validator\Input;

class UserService
{
    use Transactional;

    private const MANAGEABLE_ROLES = [Role::Student, Role::Operator];

    private UserRepository $users;
    private LoanRepository $loans;
    private PenaltyRepository $penalties;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly AuditLogger $audit,
        private readonly PasswordHasher $hasher
    ) {
        $this->users     = $em->getRepository(User::class);
        $this->loans     = $em->getRepository(Loan::class);
        $this->penalties = $em->getRepository(Penalty::class);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function search(User $actor, ?string $role, ?string $q, int $page, int $limit): array
    {
        $visible = $this->visibleRoles($actor);
        $roles   = $role !== null && $role !== '' ? array_values(array_intersect($visible, [$role])) : $visible;
        if ($roles === []) {
            return ['items' => [], 'total' => 0];
        }

        return $this->users->search($roles, $q, $page, $limit);
    }

    public function get(User $actor, int $id): User
    {
        $user = $this->users->find($id);
        if ($user === null || $user->isDeleted() || ! in_array($user->getRole()->value, $this->visibleRoles($actor), true)) {
            throw new NotFoundException('User not found.');
        }

        return $user;
    }

    public function create(User $actor, array $data): User
    {
        $input    = new Input($data);
        $name     = $input->string('name', 120);
        $email    = $input->email('email');
        $password = $this->password($input, required: true);

        $role = $input->enum('role', Role::class, self::MANAGEABLE_ROLES);
        if ($email !== null && $this->users->emailExists($email)) {
            $input->addError('email', 'This email is already in use.');
        }
        $input->assertValid();

        return $this->transactional(function () use ($actor, $name, $email, $password, $role): User {
            $user = new User((string) $name, (string) $email, $this->hasher->hash((string) $password), $role, $this->clock->now());
            $this->em->persist($user);
            $this->em->flush();
            $this->audit->log($actor, AuditLogger::USER_CREATED, 'user', $user->getId(), [
                'email' => $user->getEmail(),
                'role'  => $user->getRole()->value,
            ]);

            return $user;
        });
    }

    public function update(User $actor, int $id, array $data, bool $partial): User
    {
        $user = $this->getManageable($id);

        $input    = new Input($data, $partial);
        $name     = $input->string('name', 120);
        $email    = $input->email('email');
        $password = $this->password($input, required: false);

        $role = $input->enum('role', Role::class, self::MANAGEABLE_ROLES);
        if ($email !== null && $this->users->emailExists($email, $user->getId())) {
            $input->addError('email', 'This email is already in use.');
        }
        $input->assertValid();

        return $this->transactional(function () use ($actor, $user, $name, $email, $password, $role): User {
            $changes = [];
            if ($name !== null && $name !== $user->getName()) {
                $changes['name'] = ['old' => $user->getName(), 'new' => $name];
                $user->setName($name);
            }
            if ($email !== null && User::normalizeEmail($email) !== $user->getEmail()) {
                $changes['email'] = ['old' => $user->getEmail(), 'new' => User::normalizeEmail($email)];
                $user->setEmail($email);
            }
            if ($role !== null && $role !== $user->getRole()) {
                $changes['role'] = ['old' => $user->getRole()->value, 'new' => $role->value];
                $user->setRole($role);
            }
            if ($password !== null) {
                $changes['password'] = 'changed';
                $user->setPasswordHash($this->hasher->hash($password));
            }
            if ($changes !== []) {
                $this->audit->log($actor, AuditLogger::USER_UPDATED, 'user', $user->getId(), ['changes' => $changes]);
            }

            return $user;
        });
    }

    public function delete(User $actor, int $id): void
    {
        $user = $this->getManageable($id);
        if ($user->getId() === $actor->getId()) {
            throw new BusinessRuleException('CANNOT_DELETE_SELF', 'You cannot delete your own account.');
        }

        $this->transactional(function () use ($actor, $user): void {
            $openLoans = $this->loans->countOpenByUser($user);
            $unpaid    = $this->penalties->countUnpaidByUser($user);
            if ($openLoans > 0 || $unpaid > 0) {
                throw new BusinessRuleException(
                    'USER_HAS_OPEN_ITEMS',
                    'This account still has borrowed books or unpaid penalties and cannot be deleted.',
                    ['openLoans' => $openLoans, 'unpaidPenalties' => $unpaid]
                );
            }
            $user->markDeleted($this->clock->now());
            $this->audit->log($actor, AuditLogger::USER_DELETED, 'user', $user->getId(), [
                'email' => $user->getEmail(),
                'role'  => $user->getRole()->value,
            ]);
        });
    }

    private function visibleRoles(User $actor): array
    {
        return match ($actor->getRole()) {
            Role::SuperAdmin => [Role::Student->value, Role::Operator->value, Role::SuperAdmin->value],
            Role::Operator   => [Role::Student->value],
            Role::Student    => throw new ForbiddenException(),
        };
    }

    private function getManageable(int $id): User
    {
        $user = $this->users->find($id);
        if ($user === null || $user->isDeleted()) {
            throw new NotFoundException('User not found.');
        }
        if (! in_array($user->getRole(), self::MANAGEABLE_ROLES, true)) {
            throw new ForbiddenException('Super Admin accounts cannot be changed through the API.');
        }

        return $user;
    }

    private function password(Input $input, bool $required): ?string
    {
        $password = $input->string('password', 200, $required);
        if ($password !== null && mb_strlen($password) < 8) {
            $input->addError('password', 'Must be at least 8 characters.');
            return null;
        }

        return $password;
    }
}
