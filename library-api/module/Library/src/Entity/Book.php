<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Exception\BookUnavailableException;
use Library\Repository\BookRepository;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
#[ORM\Index(name: 'idx_books_category', columns: ['category'])]
class Book
{
    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $author;

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    private string $isbn;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $category;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $stock;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $title,
        string $author,
        string $isbn,
        string $category,
        int $stock,
        DateTimeImmutable $now
    ) {
        $this->title     = $title;
        $this->author    = $author;
        $this->isbn      = $isbn;
        $this->category  = $category;
        $this->stock     = max(0, $stock);
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function checkOut(): void
    {
        if ($this->stock <= 0) {
            throw new BookUnavailableException($this->title);
        }
        $this->stock--;
    }

    public function checkIn(): void
    {
        $this->stock++;
    }

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    public function getStatus(): string
    {
        return $this->isAvailable() ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(array $data, DateTimeImmutable $now): void
    {
        $this->title     = $data['title'] ?? $this->title;
        $this->author    = $data['author'] ?? $this->author;
        $this->isbn      = $data['isbn'] ?? $this->isbn;
        $this->category  = $data['category'] ?? $this->category;
        $this->stock     = isset($data['stock']) ? max(0, $data['stock']) : $this->stock;
        $this->updatedAt = $now;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function markDeleted(DateTimeImmutable $at): void
    {
        $this->deletedAt = $at;
        $this->updatedAt = $at;
    }

    public function restore(DateTimeImmutable $at): void
    {
        $this->deletedAt = null;
        $this->updatedAt = $at;
    }
}
