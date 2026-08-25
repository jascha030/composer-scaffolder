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

use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Question\TextQuestion;

use function array_keys;
use function array_map;
use function in_array;
use function trim;

final class AnswerResolver
{
    public function resolve(
        Manifest $manifest,
        AnswerBag $predefined,
        AnswerProvider $provider,
    ): AnswerBag {
        $knownKeys = array_map(
            static fn ($question): string => $question->key,
            $manifest->questions,
        );

        $this->guardKnownKeys($predefined, $knownKeys);

        $collected = $provider->collect($manifest, $predefined);

        $this->guardKnownKeys($collected, $knownKeys);

        $resolved = new AnswerBag();

        foreach ($manifest->questions as $question) {
            $value = $collected->has($question->key)
                ? $collected->get($question->key)
                : $question->default;

            if (! $question instanceof TextQuestion) {
                $resolved = $resolved->with($question->key, $value);

                continue;
            }

            $resolved = $resolved->with(
                $question->key,
                $this->resolveTextAnswer($question, $value),
            );
        }

        return $resolved;
    }

    /**
     * @param list<string> $knownKeys
     */
    private function guardKnownKeys(AnswerBag $answers, array $knownKeys): void
    {
        foreach (array_keys($answers->all()) as $key) {
            if (! in_array($key, $knownKeys, true)) {
                throw InvalidAnswerException::unknownKey($key);
            }
        }
    }

    private function resolveTextAnswer(TextQuestion $question, ?string $value): ?string
    {
        $trimmed = null === $value ? null : trim($value);

        if ($question->isMissing($trimmed)) {
            throw InvalidAnswerException::requiredMissing($question->key);
        }

        if (null === $trimmed || '' === $trimmed) {
            return null;
        }

        if (! $question->patternMatches($trimmed)) {
            throw InvalidAnswerException::patternMismatch($question->key, $trimmed, $question->pattern ?? '');
        }

        return $trimmed;
    }
}
