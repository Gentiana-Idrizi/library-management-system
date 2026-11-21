<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'system_settings')]
class SystemSetting
{
    #[ORM\Id]
    #[ORM\Column(name: 'setting_key', type: Types::STRING, length: 64)]
    private string $key;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $value;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $key, string $value, DateTimeImmutable $updatedAt)
    {
        $this->key       = $key;
        $this->value     = $value;
        $this->updatedAt = $updatedAt;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value, DateTimeImmutable $updatedAt): void
    {
        $this->value     = $value;
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
