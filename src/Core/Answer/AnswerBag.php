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
        if (array_key_exists($key, $this->answers)) {
            return $this;
        }

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
