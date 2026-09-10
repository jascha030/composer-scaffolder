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

namespace Jascha030\Scaffolder\Core\Contract;

interface ProjectFilesystem
{
    public function pathExists(string $path): bool;

    public function isLink(string $path): bool;

    public function isDirectory(string $path): bool;

    public function isEmptyDirectory(string $path): bool;

    public function assertDestinationAvailable(string $destination, bool $force): void;

    public function ensureDirectory(string $path, int $mode = 0o755): void;

    public function createDirectory(string $path, int $mode = 0o755): string;

    public function readFile(string $path): string;

    public function writeFile(string $path, string $contents): void;

    public function copyFile(string $source, string $target): void;

    public function copyPermissions(string $source, string $target): void;

    public function move(string $source, string $target): void;

    public function remove(string $path): void;
}
