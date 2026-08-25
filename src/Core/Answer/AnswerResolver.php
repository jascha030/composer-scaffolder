<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Answer;

use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;

use function array_fill_keys;
use function array_keys;
use function in_array;

final class AnswerResolver
{
    public function __construct(private readonly AnswerValidator $validator = new AnswerValidator())
    {
    }

    public function resolve(
        Manifest $manifest,
        AnswerBag $predefined,
        AnswerProvider $provider,
    ): AnswerBag {
        $knownKeys = array_keys(array_fill_keys(
            array_map(static fn ($question): string => $question->key, $manifest->questions),
            true,
        ));

        foreach (array_keys($predefined->all()) as $key) {
            if (! in_array($key, $knownKeys, true)) {
                throw InvalidAnswerException::unknownKey($key);
            }
        }

        $collected = $provider->collect($manifest, $predefined);

        foreach (array_keys($collected->all()) as $key) {
            if (! in_array($key, $knownKeys, true)) {
                throw InvalidAnswerException::unknownKey($key);
            }
        }

        $resolved = new AnswerBag();

        foreach ($manifest->questions as $question) {
            $value = $collected->has($question->key)
                ? $collected->get($question->key)
                : $question->default;

            $resolved = $resolved->with(
                $question->key,
                $this->validator->validate($question, $value),
            );
        }

        return $resolved;
    }
}
