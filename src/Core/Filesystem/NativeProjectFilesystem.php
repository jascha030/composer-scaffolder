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

namespace Jascha030\Scaffolder\Core\Filesystem;

use Jascha030\Scaffolder\Core\Contract\ProjectFilesystem;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class NativeProjectFilesystem implements ProjectFilesystem
{
    public function pathExists(string $path): bool
    {
        return file_exists($path) || is_link($path);
    }

    public function isLink(string $path): bool
    {
        return is_link($path);
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function isEmptyDirectory(string $path): bool
    {
        $entries = @scandir($path);

        if (false === $entries) {
            return false;
        }

        return [] === array_diff($entries, ['.', '..']);
    }

    public function assertDestinationAvailable(string $destination, bool $force): void
    {
        if (! $this->pathExists($destination)) {
            return;
        }

        if ($this->isLink($destination)
            || ! $this->isDirectory($destination)
            || ! $this->isEmptyDirectory($destination)
        ) {
            throw FilesystemException::destinationExists($destination);
        }

        if (! $force) {
            throw FilesystemException::emptyDestinationRequiresForce($destination);
        }
    }

    public function ensureDirectory(string $path, int $mode = 0o755): void
    {
        if (! is_dir($path) && ! @mkdir($path, $mode, true) && ! is_dir($path)) {
            throw FilesystemException::cannotCreateDirectory($path);
        }
    }

    public function createDirectory(string $path, int $mode = 0o755): string
    {
        if (! @mkdir($path, $mode, true) || false === ($realPath = realpath($path))) {
            throw FilesystemException::cannotCreateDirectory($path);
        }

        return $realPath;
    }

    public function readFile(string $path): string
    {
        $contents = @file_get_contents($path);

        if (false === $contents) {
            throw FilesystemException::cannotReadFile($path);
        }

        return $contents;
    }

    public function writeFile(string $path, string $contents): void
    {
        if (false === @file_put_contents($path, $contents)) {
            throw FilesystemException::cannotWriteFile($path);
        }
    }

    public function copyFile(string $source, string $target): void
    {
        if (! @copy($source, $target)) {
            throw FilesystemException::cannotWriteFile($target);
        }
    }

    public function copyPermissions(string $source, string $target): void
    {
        $permissions = @fileperms($source);

        if (false !== $permissions && ! @chmod($target, $permissions & 0o7777)) {
            throw FilesystemException::cannotWriteFile($target);
        }
    }

    public function move(string $source, string $target): void
    {
        if (! @rename($source, $target)) {
            throw FilesystemException::cannotMove($source, $target);
        }
    }

    public function remove(string $path): void
    {
        if (is_link($path) || ! is_dir($path)) {
            if (file_exists($path) && ! @unlink($path)) {
                throw FilesystemException::cannotRemove($path);
            }

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (! $item instanceof SplFileInfo) {
                continue;
            }

            $removed = $item->isLink() || $item->isFile()
                ? @unlink($item->getPathname())
                : @rmdir($item->getPathname());

            if (! $removed) {
                throw FilesystemException::cannotRemove($item->getPathname());
            }
        }

        if (! @rmdir($path)) {
            throw FilesystemException::cannotRemove($path);
        }
    }
}
