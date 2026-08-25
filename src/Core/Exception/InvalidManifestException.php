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

namespace Jascha030\Scaffolder\Core\Exception;

use function sprintf;

final class InvalidManifestException extends ScaffolderException
{
    public static function invalidJson(string $path, string $error): self
    {
        return new self(sprintf('Manifest "%s" contains invalid JSON: %s', $path, $error));
    }

    public static function notAnObject(string $path): self
    {
        return new self(sprintf('Manifest "%s" root value must be a JSON object.', $path));
    }

    public static function unsupportedSchema(int $schema, int $supported): self
    {
        return new self(sprintf('Unsupported manifest schema "%d". Only schema "%d" is supported.', $schema, $supported));
    }

    public static function invalidQuestionsType(): self
    {
        return new self('Manifest "questions" must be an array.');
    }

    public static function invalidFilesType(): self
    {
        return new self('Manifest "files" must be an array.');
    }

    public static function invalidQuestionType(string $type): self
    {
        return new self(sprintf('Unsupported question type "%s".', $type));
    }

    public static function missingQuestionField(string $field): self
    {
        return new self(sprintf('Question is missing required field "%s".', $field));
    }

    public static function invalidValidationType(): self
    {
        return new self('Question "validation" must be an object.');
    }

    public static function missingFileField(string $field): self
    {
        return new self(sprintf('File operation is missing required field "%s".', $field));
    }

    public static function unexpectedField(string $context, string $field): self
    {
        return new self(sprintf('Unexpected field "%s" in %s.', $field, $context));
    }

    public static function duplicateQuestion(string $key): self
    {
        return new self(sprintf('Question key "%s" is defined more than once.', $key));
    }

    public static function invalidPattern(string $pattern): self
    {
        return new self(sprintf('Question validation pattern "%s" is not a valid regular expression.', $pattern));
    }

    public static function invalidField(string $context, string $field): self
    {
        return new self(sprintf('Field "%s" in %s has an invalid value.', $field, $context));
    }

    public static function invalidMode(string $mode): self
    {
        return new self(sprintf('Unsupported file operation mode "%s".', $mode));
    }
}
