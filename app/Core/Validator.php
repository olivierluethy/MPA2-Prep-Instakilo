<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Very small, dependency-free validator. Accumulates field errors that
 * controllers can surface as flash messages or JSON.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message): self
    {
        if (trim((string) ($this->data[$field] ?? '')) === '') {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function email(string $field, string $message): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function min(string $field, int $length, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function max(string $field, int $length, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $length) {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function matches(string $field, string $other, string $message): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$other] ?? null)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function addError(string $field, string $message): void
    {
        // Keep the first error per field.
        $this->errors[$field] ??= $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }
}
