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
use Jascha030\Scaffolder\Core\Question\TextQuestion;
use RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function is_string;

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
        $answers = $predefinedAnswers;

        foreach ($manifest->questions as $question) {
            if ($answers->has($question->key) || ! $this->input->isInteractive()) {
                continue;
            }

            if (! $question instanceof TextQuestion) {
                continue;
            }

            $answers = $answers->with($question->key, $this->ask($question));
        }

        return $answers;
    }

    private function ask(TextQuestion $question): ?string
    {
        $symfonyQuestion = new Question($question->prompt, $question->default);
        $symfonyQuestion->setValidator(static function (mixed $value) use ($question): ?string {
            try {
                return $question->resolveAnswer($value);
            } catch (InvalidAnswerException $exception) {
                throw new RuntimeException($exception->getMessage(), 0, $exception);
            }
        });

        $answer = $this->questionHelper->ask($this->input, $this->output, $symfonyQuestion);

        if (null !== $answer && ! is_string($answer)) {
            throw InvalidAnswerException::invalidType($question->key);
        }

        return $question->resolveAnswer($answer);
    }
}
