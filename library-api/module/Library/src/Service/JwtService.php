<?php

declare(strict_types=1);

namespace Library\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Library\Entity\User;
use Library\Exception\UnauthorizedException;
use Throwable;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
        private readonly string $issuer,
        private readonly ClockInterface $clock
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('library.jwt.secret must be at least 32 characters long.');
        }
    }

    public function issue(User $user): array
    {
        $now     = $this->clock->now()->getTimestamp();
        $expires = $now + $this->ttlSeconds;
        $payload = [
            'iss'  => $this->issuer,
            'sub'  => (string) $user->getId(),
            'role' => $user->getRole()->value,
            'iat'  => $now,
            'exp'  => $expires,
        ];

        return ['token' => JWT::encode($payload, $this->secret, self::ALGORITHM), 'expiresAt' => $expires];
    }

    public function verify(string $token): int
    {
        try {
            JWT::$timestamp = $this->clock->now()->getTimestamp();
            $payload        = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (Throwable) {
            throw new UnauthorizedException('Invalid or expired token.');
        } finally {
            JWT::$timestamp = null;
        }

        if (($payload->iss ?? null) !== $this->issuer || ! isset($payload->sub) || ! ctype_digit((string) $payload->sub)) {
            throw new UnauthorizedException('Invalid or expired token.');
        }

        return (int) $payload->sub;
    }
}
