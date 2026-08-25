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

final class PlanningException extends ScaffolderException
{
    public static function absoluteSource(string $path): self
    {
        return new self(sprintf('Source path "%s" must be relative.', $path));
    }

    public static function absoluteTarget(string $path): self
    {
        return new self(sprintf('Target path "%s" must be relative.', $path));
    }

    public static function traversalSource(string $path): self
    {
        return new self(sprintf('Source path "%s" must not contain "..".', $path));
    }

    public static function traversalTarget(string $path): self
    {
        return new self(sprintf('Target path "%s" must not contain "..".', $path));
    }

    public static function sourceOutsidePayload(string $path): self
    {
        return new self(sprintf('Source file "%s" is outside the template payload.', $path));
    }

    public static function targetOutsideDestination(string $path): self
    {
        return new self(sprintf('Target path "%s" is outside the destination directory.', $path));
    }

    public static function missingSource(string $path): self
    {
        return new self(sprintf('Source file "%s" does not exist.', $path));
    }

    public static function duplicateTarget(string $path): self
    {
        return new self(sprintf('Target path "%s" is defined more than once.', $path));
    }

    public static function targetConflict(string $first, string $second): self
    {
        return new self(sprintf('Target paths "%s" and "%s" conflict as file and directory.', $first, $second));
    }

    public static function invalidDestination(string $path): self
    {
        return new self(sprintf('Destination path "%s" is not safe.', $path));
    }

    public static function unsupportedMode(string $mode): self
    {
        return new self(sprintf('Unsupported file operation mode "%s".', $mode));
    }

    public static function symlinkEscape(string $path): self
    {
        return new self(sprintf('Source file "%s" is a symlink that escapes the template payload.', $path));
    }
}
