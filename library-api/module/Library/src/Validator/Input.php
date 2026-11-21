<?php

declare(strict_types=1);

namespace Library\Validator;

use Library\Exception\ValidationException;
use Library\Service\Money;

final class Input
{
    private array $errors = [];

    public function __construct(private readonly array $data, private readonly bool $partial = false)
    {
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data) && $this->data[$field] !== null;
    }

    public function string(string $field, int $maxLength = 255, bool $required = true): ?string
    {
        if (! $this->has($field)) {
            $this->missing($field, $required);
            return null;
        }
        $value = $this->data[$field];
        if (! is_string($value) && ! is_int($value)) {
            $this->errors[$field] = 'Must be a string.';
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            $this->missing($field, $required);
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            $this->errors[$field] = sprintf('Must be at most %d characters.', $maxLength);
            return null;
        }
        return $value;
    }

    public function email(string $field, bool $required = true): ?string
    {
        $value = $this->string($field, 180, $required);
        if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Must be a valid email address.';
            return null;
        }
        return $value;
    }

    public function int(string $field, int $min, int $max, bool $required = true): ?int
    {
        if (! $this->has($field)) {
            $this->missing($field, $required);
            return null;
        }
        $value = filter_var($this->data[$field], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $this->errors[$field] = sprintf('Must be a whole number between %d and %d.', $min, $max);
            return null;
        }
        return $value;
    }

    public function money(string $field, bool $required = true): ?string
    {
        if (! $this->has($field)) {
            $this->missing($field, $required);
            return null;
        }
        $raw = $this->data[$field];
        $raw = is_float($raw) || is_int($raw) ? (string) $raw : $raw;
        if (! is_string($raw) || ! Money::isValid($raw)) {
            $this->errors[$field] = 'Must be a non-negative amount with at most 2 decimals.';
            return null;
        }
        return Money::fromCents(Money::toCents($raw));
    }

    public function enum(string $field, string $enumClass, ?array $allowed = null, bool $required = true): ?\BackedEnum
    {
        if (! $this->has($field)) {
            $this->missing($field, $required);
            return null;
        }
        $value = is_string($this->data[$field]) ? $enumClass::tryFrom($this->data[$field]) : null;
        if ($value === null || ($allowed !== null && ! in_array($value, $allowed, true))) {
            $options = array_map(static fn(\BackedEnum $e) => $e->value, $allowed ?? $enumClass::cases());
            $this->errors[$field] = 'Must be one of: ' . implode(', ', $options) . '.';
            return null;
        }
        return $value;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function assertValid(): void
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }
    }

    private function missing(string $field, bool $required): void
    {
        if ($required && ! $this->partial) {
            $this->errors[$field] = 'This field is required.';
        }
    }
}
