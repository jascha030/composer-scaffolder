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
use Jascha030\Scaffolder\Core\Operation\OperationMode;

use function is_array;
use function is_int;
use function is_string;

final class ManifestValidator
{
    public const SUPPORTED_SCHEMA = 1;

    /**
     * @param array<mixed, mixed> $data
     */
    public function validate(array $data): void
    {
        if (! isset($data['schema']) || ! is_int($data['schema']) || self::SUPPORTED_SCHEMA !== $data['schema']) {
            throw InvalidManifestException::unsupportedSchema(isset($data['schema']) && is_int($data['schema']) ? $data['schema'] : 0, self::SUPPORTED_SCHEMA);
        }

        if (isset($data['questions']) && ! is_array($data['questions'])) {
            throw InvalidManifestException::invalidQuestionsType();
        }

        if (isset($data['files']) && ! is_array($data['files'])) {
            throw InvalidManifestException::invalidFilesType();
        }

        foreach ($data['questions'] ?? [] as $question) {
            $this->validateQuestion($question);
        }

        foreach ($data['files'] ?? [] as $file) {
            $this->validateFile($file);
        }
    }

    private function validateQuestion(mixed $question): void
    {
        if (! is_array($question)) {
            throw InvalidManifestException::invalidQuestionsType();
        }

        if (! isset($question['key']) || ! is_string($question['key'])) {
            throw InvalidManifestException::missingQuestionField('key');
        }

        if (! isset($question['type']) || ! is_string($question['type'])) {
            throw InvalidManifestException::missingQuestionField('type');
        }

        if (! isset($question['prompt']) || ! is_string($question['prompt'])) {
            throw InvalidManifestException::missingQuestionField('prompt');
        }

        if ('text' !== $question['type']) {
            throw InvalidManifestException::invalidQuestionType($question['type']);
        }

        if (isset($question['validation']) && ! is_array($question['validation'])) {
            throw InvalidManifestException::invalidValidationType();
        }
    }

    private function validateFile(mixed $file): void
    {
        if (! is_array($file)) {
            throw InvalidManifestException::invalidFilesType();
        }

        if (! isset($file['source']) || ! is_string($file['source']) || '' === $file['source']) {
            throw InvalidManifestException::missingFileField('source');
        }

        if (! isset($file['target']) || ! is_string($file['target']) || '' === $file['target']) {
            throw InvalidManifestException::missingFileField('target');
        }

        if (! isset($file['mode']) || ! is_string($file['mode'])) {
            throw InvalidManifestException::missingFileField('mode');
        }

        if (! OperationMode::tryFrom($file['mode']) instanceof OperationMode) {
            throw InvalidManifestException::invalidMode($file['mode']);
        }
    }
}
