<?php

/*
 * This file is part of the jascha030/composer-scaffolder package.
 *
 * (c) Jascha van Aalst <contact@jaschavanaalst.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Answer;

use function array_key_exists;

final class AnswerBag
{
    /**
     * @param array<string, mixed> $answers
     */
    public function __construct(private readonly array $answers)
    {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->answers);
    }

    public function get(string $key): mixed
    {
        return $this->answers[$key] ?? null;
    }

    public function with(string $key, mixed $value): self
    {
        if ($this->has($key)) {
            return $this;
        }

        $answers       = $this->answers;
        $answers[$key] = $value;

        return new self($answers);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->answers;
    }
}
