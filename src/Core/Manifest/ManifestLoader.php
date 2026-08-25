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

namespace Jascha030\Scaffolder\Core\Manifest;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Question\QuestionDefinition;
use Jascha030\Scaffolder\Core\Question\TextQuestion;

use function file_get_contents;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function trim;

use const JSON_ERROR_NONE;

final class ManifestLoader
{
    public function __construct(private readonly ManifestValidator $validator = new ManifestValidator())
    {
    }

    public function load(string $path): Manifest
    {
        $contents = @file_get_contents($path);

        if (false === $contents) {
            throw InvalidManifestException::invalidJson($path, 'Unable to read file.');
        }

        $data = json_decode(trim($contents), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidManifestException::invalidJson($path, json_last_error_msg());
        }

        if (! is_array($data)) {
            throw InvalidManifestException::notAnObject($path);
        }

        $this->validator->validate($data);

        $schema    = $this->intField($data, 'schema');
        $questions = $this->arrayField($data, 'questions');
        $files     = $this->arrayField($data, 'files');

        return new Manifest(
            $schema,
            $this->loadQuestions($questions),
            $this->loadFiles($files),
        );
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function intField(array $data, string $field): int
    {
        if (! isset($data[$field]) || ! is_int($data[$field])) {
            throw InvalidManifestException::missingFileField($field);
        }

        return $data[$field];
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array<mixed, mixed>
     */
    private function arrayField(array $data, string $field): array
    {
        if (! isset($data[$field]) || ! is_array($data[$field])) {
            return [];
        }

        return $data[$field];
    }

    /**
     * @param array<mixed, mixed> $questions
     *
     * @return list<QuestionDefinition>
     */
    private function loadQuestions(array $questions): array
    {
        $definitions = [];

        foreach ($questions as $question) {
            if (! is_array($question)) {
                throw InvalidManifestException::invalidQuestionsType();
            }

            $definitions[] = $this->loadQuestion($question);
        }

        return $definitions;
    }

    /**
     * @param array<mixed, mixed> $question
     */
    private function loadQuestion(array $question): TextQuestion
    {
        $validation = is_array($question['validation'] ?? null) ? $question['validation'] : [];

        return new TextQuestion(
            $this->stringField($question, 'key'),
            $this->stringField($question, 'prompt'),
            $question['default'] ?? null,
            $this->boolField($question, 'required'),
            isset($validation['pattern']) && is_string($validation['pattern']) ? $validation['pattern'] : null,
        );
    }

    /**
     * @param array<mixed, mixed> $files
     *
     * @return list<FileOperation>
     */
    private function loadFiles(array $files): array
    {
        $operations = [];

        foreach ($files as $file) {
            if (! is_array($file)) {
                throw InvalidManifestException::invalidFilesType();
            }

            $operations[] = new FileOperation(
                $this->stringField($file, 'source'),
                $this->stringField($file, 'target'),
                OperationMode::from($this->stringField($file, 'mode')),
            );
        }

        return $operations;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function stringField(array $data, string $field): string
    {
        if (! isset($data[$field]) || ! is_string($data[$field])) {
            throw InvalidManifestException::missingFileField($field);
        }

        return $data[$field];
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function boolField(array $data, string $field): bool
    {
        return isset($data[$field]) && is_bool($data[$field]) && $data[$field];
    }
}
