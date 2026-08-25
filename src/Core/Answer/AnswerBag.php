<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Answer;

use function array_key_exists;

final class AnswerBag
{
    /**
     * @param array<string, string|null> $answers
     */
    public function __construct(private readonly array $answers = [])
    {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->answers);
    }

    public function get(string $key): ?string
    {
        return $this->answers[$key] ?? null;
    }

    public function with(string $key, ?string $value): self
    {
        $answers       = $this->answers;
        $answers[$key] = $value;

        return new self($answers);
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->answers;
    }
}
