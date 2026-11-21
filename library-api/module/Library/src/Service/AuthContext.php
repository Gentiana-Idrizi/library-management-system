<?php

declare(strict_types=1);

namespace Library\Service;

use Library\Entity\Role;
use Library\Entity\User;
use Library\Exception\ForbiddenException;
use Library\Exception\UnauthorizedException;

class AuthContext
{
    private ?User $user = null;

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function requireUser(): User
    {
        return $this->user ?? throw new UnauthorizedException();
    }

    public function requireRole(Role ...$roles): User
    {
        $user = $this->requireUser();
        if (! in_array($user->getRole(), $roles, true)) {
            throw new ForbiddenException();
        }

        return $user;
    }

    public function requireStaff(): User
    {
        return $this->requireRole(Role::Operator, Role::SuperAdmin);
    }
}
