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

final class InvalidTemplateException extends ScaffolderException
{
    public static function missingComposerJson(string $path): self
    {
        return new self(sprintf('Template package "%s" does not contain a composer.json file.', $path));
    }

    public static function invalidJson(string $path, string $error): self
    {
        return new self(sprintf('Template package composer.json at "%s" contains invalid JSON: %s', $path, $error));
    }

    public static function invalidRootType(string $expected): self
    {
        return new self(sprintf('Template package type must be exactly "%s".', $expected));
    }

    public static function missingExtra(string $key): self
    {
        return new self(sprintf('Template package must contain "extra.%s" metadata.', $key));
    }

    public static function invalidExtra(string $key): self
    {
        return new self(sprintf('Template package "extra.%s" metadata must be an object.', $key));
    }

    public static function unsupportedSchema(int $schema, int $supported): self
    {
        return new self(sprintf('Unsupported scaffold schema "%d". Only schema "%d" is supported.', $schema, $supported));
    }

    public static function missingManifest(string $path): self
    {
        return new self(sprintf('Scaffold manifest "%s" does not exist.', $path));
    }

    public static function missingPayload(string $path): self
    {
        return new self(sprintf('Scaffold payload directory "%s" does not exist.', $path));
    }

    public static function absolutePath(string $key): self
    {
        return new self(sprintf('Scaffold metadata "%s" must be a relative path.', $key));
    }

    public static function unsafePath(string $key): self
    {
        return new self(sprintf('Scaffold metadata "%s" must not escape the template package using "..".', $key));
    }

    public static function symlinkEscape(string $key): self
    {
        return new self(sprintf('Scaffold metadata "%s" resolves to a symlink outside the template package.', $key));
    }

    public static function unexpectedField(string $field): self
    {
        return new self(sprintf('Unexpected scaffold metadata field "%s".', $field));
    }

    public static function missingField(string $field): self
    {
        return new self(sprintf('Scaffold metadata is missing required field "%s".', $field));
    }
}
