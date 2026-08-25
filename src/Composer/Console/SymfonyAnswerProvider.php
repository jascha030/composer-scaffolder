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

namespace Jascha030\Scaffolder\Composer\Console;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Question\QuestionDefinition;
use Jascha030\Scaffolder\Core\Question\TextQuestion;
use RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function is_string;
use function preg_match;
use function str_replace;
use function trim;

final class SymfonyAnswerProvider implements AnswerProvider
{
    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output,
        private readonly QuestionHelper $questionHelper,
    ) {
    }

    public function collect(Manifest $manifest, AnswerBag $predefinedAnswers): AnswerBag
    {
        $bag = new AnswerBag([]);

        foreach ($manifest->questions as $question) {
            $value = $this->resolveValue($question, $predefinedAnswers);
            $this->validateValue($question, $value);
            $bag = $bag->with($question->key, $value);
        }

        return $bag;
    }

    private function resolveValue(QuestionDefinition $question, AnswerBag $predefined): mixed
    {
        if ($predefined->has($question->key)) {
            return $predefined->get($question->key);
        }

        if (null !== $question->default) {
            return $question->default;
        }

        if (! $this->input->isInteractive()) {
            if ($question->required) {
                throw InvalidAnswerException::requiredMissing($question->key);
            }

            return null;
        }

        return $this->ask($question);
    }

    private function ask(QuestionDefinition $question): mixed
    {
        if (! $question instanceof TextQuestion) {
            throw InvalidAnswerException::requiredMissing($question->key);
        }

        $symfonyQuestion = new Question($question->prompt);

        $symfonyQuestion->setValidator(function ($value) use ($question): string {
            $stringValue = $this->stringValue($value);

            if ('' === $stringValue && $question->required) {
                throw new RuntimeException(InvalidAnswerException::requiredMissing($question->key)->getMessage());
            }

            if ('' !== $stringValue && null !== $question->pattern && 1 !== preg_match($this->compilePattern($question->pattern), $stringValue)) {
                throw new RuntimeException(InvalidAnswerException::patternMismatch($question->key, $stringValue, $question->pattern)->getMessage());
            }

            return $stringValue;
        });

        $answer = $this->questionHelper->ask($this->input, $this->output, $symfonyQuestion);

        return is_string($answer) ? trim($answer) : $answer;
    }

    private function validateValue(QuestionDefinition $question, mixed $value): void
    {
        if (! $question instanceof TextQuestion) {
            return;
        }

        $stringValue = $this->stringValue($value);

        if ('' === $stringValue && $question->required) {
            throw InvalidAnswerException::requiredMissing($question->key);
        }

        if ('' !== $stringValue && null !== $question->pattern && 1 !== preg_match($this->compilePattern($question->pattern), $stringValue)) {
            throw InvalidAnswerException::patternMismatch($question->key, $stringValue, $question->pattern);
        }
    }

    private function stringValue(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (! is_string($value)) {
            throw InvalidAnswerException::patternMismatch('?', '', 'value must be a string');
        }

        return trim($value);
    }

    private function compilePattern(string $pattern): string
    {
        if (1 === preg_match('/^([^a-zA-Z0-9\\\]).*\1$/s', $pattern)) {
            return $pattern;
        }

        return '~' . str_replace('~', '\~', $pattern) . '~';
    }
}
