<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\User;
use Library\Exception\UnauthorizedException;
use Library\Repository\UserRepository;
use Library\Validator\Input;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasher $hasher,
        private readonly JwtService $jwt
    ) {
    }

    private function users(): UserRepository
    {
        return $this->em->getRepository(User::class);
    }

    public function login(array $data): array
    {
        $input    = new Input($data);
        $email    = $input->string('email', 180);
        $password = $input->string('password', 200);
        $input->assertValid();

        $user = $this->users()->findActiveByEmail((string) $email);

        if ($user === null || ! $this->hasher->verify((string) $password, $user->getPasswordHash())) {
            throw new UnauthorizedException('Invalid email or password.');
        }

        if ($this->hasher->needsRehash($user->getPasswordHash())) {
            $user->setPasswordHash($this->hasher->hash((string) $password));
            $this->em->flush();
        }

        return $this->jwt->issue($user) + ['user' => $user];
    }

    public function userFromToken(string $token): User
    {
        $user = $this->users()->find($this->jwt->verify($token));
        if ($user === null || $user->isDeleted()) {
            throw new UnauthorizedException('Account not found or disabled.');
        }

        return $user;
    }
}
