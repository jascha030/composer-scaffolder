<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Manifest;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Question\TextQuestion;

use function array_key_exists;
use function array_keys;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

final class ManifestValidator
{
    private const ROOT_FIELDS = ['schema', 'questions', 'files'];
    private const QUESTION_FIELDS = ['key', 'type', 'prompt', 'default', 'required', 'validation'];
    private const FILE_FIELDS = ['source', 'target', 'mode'];

    /**
     * @param array<mixed, mixed> $data
     */
    public function validate(array $data): Manifest
    {
        $this->assertOnlyFields($data, self::ROOT_FIELDS, 'manifest');

        $schema = $this->intField($data, 'schema');

        $questions = [];
        $seenKeys  = [];

        foreach ($this->arrayField($data, 'questions') as $question) {
            if (! is_array($question)) {
                throw InvalidManifestException::invalidQuestionsType();
            }

            $this->assertOnlyFields($question, self::QUESTION_FIELDS, 'question');

            $key = $this->stringField($question, 'key', false);

            if (in_array($key, $seenKeys, true)) {
                throw InvalidManifestException::duplicateQuestion($key);
            }

            $seenKeys[] = $key;

            $type = $this->stringField($question, 'type', false);
            if ('text' !== $type) {
                throw InvalidManifestException::invalidQuestionType($type);
            }

            $questions[] = new TextQuestion(
                $key,
                $this->stringField($question, 'prompt', false),
                $this->optionalString($question['default'] ?? null, 'question', 'default'),
                $this->optionalBool($question['required'] ?? null, 'question', 'required'),
                $this->optionalPattern($question['validation'] ?? null),
            );
        }

        $files = [];

        foreach ($this->arrayField($data, 'files') as $file) {
            if (! is_array($file)) {
                throw InvalidManifestException::invalidFilesType();
            }

            $this->assertOnlyFields($file, self::FILE_FIELDS, 'file operation');

            $modeString = $this->stringField($file, 'mode', true);
            $mode       = OperationMode::tryFrom($modeString);

            if (null === $mode) {
                throw InvalidManifestException::invalidMode($modeString);
            }

            $files[] = new FileOperation(
                $this->stringField($file, 'source', true),
                $this->stringField($file, 'target', true),
                $mode,
            );
        }

        return new Manifest($schema, $questions, $files);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function intField(array $data, string $field): int
    {
        if (! array_key_exists($field, $data) || ! is_int($data[$field])) {
            throw InvalidManifestException::unsupportedSchema(is_int($data[$field] ?? null) ? $data[$field] : 0, Manifest::SUPPORTED_SCHEMA);
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
        if (! array_key_exists($field, $data)) {
            return [];
        }

        if (! is_array($data[$field])) {
            throw InvalidManifestException::invalidField('manifest', $field);
        }

        return $data[$field];
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function stringField(array $data, string $field, bool $isFile): string
    {
        if (! array_key_exists($field, $data) || ! is_string($data[$field])) {
            throw $isFile
                ? InvalidManifestException::missingFileField($field)
                : InvalidManifestException::missingQuestionField($field);
        }

        return $data[$field];
    }

    private function optionalString(mixed $value, string $context, string $field): ?string
    {
        if (null !== $value && ! is_string($value)) {
            throw InvalidManifestException::invalidField($context, $field);
        }

        return $value;
    }

    private function optionalBool(mixed $value, string $context, string $field): bool
    {
        if (null === $value) {
            return false;
        }

        if (! is_bool($value)) {
            throw InvalidManifestException::invalidField($context, $field);
        }

        return $value;
    }

    private function optionalPattern(mixed $validation): ?string
    {
        if (null === $validation) {
            return null;
        }

        if (! is_array($validation)) {
            throw InvalidManifestException::invalidValidationType();
        }

        $this->assertOnlyFields($validation, ['pattern'], 'question validation');

        $pattern = $validation['pattern'] ?? null;

        if (null === $pattern) {
            return null;
        }

        if (! is_string($pattern)) {
            throw InvalidManifestException::invalidField('question validation', 'pattern');
        }

        return $pattern;
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<string>        $allowed
     */
    private function assertOnlyFields(array $data, array $allowed, string $context): void
    {
        foreach (array_keys($data) as $field) {
            if (! is_string($field) || ! in_array($field, $allowed, true)) {
                throw InvalidManifestException::unexpectedField($context, (string) $field);
            }
        }
    }
}
