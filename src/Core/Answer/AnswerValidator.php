<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Answer;

use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Question\TextQuestion;
use Jascha030\Scaffolder\Core\Validation\RegularExpression;

use function trim;

final class AnswerValidator
{
    public function validate(TextQuestion $question, ?string $value): ?string
    {
        $value = null === $value ? null : trim($value);

        if ((null === $value || '' === $value) && $question->required) {
            throw InvalidAnswerException::requiredMissing($question->key);
        }

        if (null !== $value && '' !== $value && null !== $question->pattern
            && 1 !== preg_match(RegularExpression::compile($question->pattern), $value)) {
            throw InvalidAnswerException::patternMismatch($question->key, $value, $question->pattern);
        }

        return $value;
    }
}
