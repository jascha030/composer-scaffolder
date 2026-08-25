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

use Throwable;

use function sprintf;

final class FilesystemException extends ScaffolderException
{
    public static function cannotCreateDirectory(string $path): self
    {
        return new self(sprintf('Failed to create directory "%s".', $path));
    }

    public static function cannotWriteFile(string $path): self
    {
        return new self(sprintf('Failed to write file "%s".', $path));
    }

    public static function cannotReadFile(string $path): self
    {
        return new self(sprintf('Failed to read file "%s".', $path));
    }

    public static function cannotRemove(string $path): self
    {
        return new self(sprintf('Failed to remove "%s".', $path));
    }

    public static function cleanupFailed(string $path, Throwable $cleanup, Throwable $original): self
    {
        return new self(sprintf(
            'Generation failed with "%s" and staging cleanup of "%s" also failed: %s',
            $original->getMessage(),
            $path,
            $cleanup->getMessage(),
        ), 0, $original);
    }

    public static function cannotMove(string $source, string $target): self
    {
        return new self(sprintf('Failed to move "%s" to "%s".', $source, $target));
    }

    public static function destinationExists(string $path): self
    {
        return new self(sprintf('Destination "%s" already exists and is not empty.', $path));
    }

    public static function emptyDestinationRequiresForce(string $path): self
    {
        return new self(sprintf('Destination "%s" already exists. Use --force to replace an empty destination.', $path));
    }
}
