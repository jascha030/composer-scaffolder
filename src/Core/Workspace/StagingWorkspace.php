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

namespace Jascha030\Scaffolder\Core\Workspace;

use Jascha030\Scaffolder\Core\Contract\ProjectFilesystem;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
use Throwable;

use function dirname;
use function random_bytes;

final class StagingWorkspace
{
    public function __construct(
        private readonly string $stagingPath,
        private readonly string $destinationPath,
        private readonly ProjectFilesystem $filesystem,
    ) {
    }

    public static function create(
        string $destination,
        ProjectFilesystem $filesystem,
    ): self {
        $parent = dirname($destination);
        $filesystem->ensureDirectory($parent);

        $staging = $parent . '/.' . basename($destination) . '.scaffold.' . bin2hex(random_bytes(12));

        return new self(
            $filesystem->createDirectory($staging, 0o700),
            $destination,
            $filesystem,
        );
    }

    public function stagingPath(): string
    {
        return $this->stagingPath;
    }

    public function publish(bool $force): void
    {
        if ($this->filesystem->pathExists($this->destinationPath)) {
            $this->filesystem->assertDestinationAvailable($this->destinationPath, $force);
            $this->filesystem->remove($this->destinationPath);
        }

        $this->filesystem->move($this->stagingPath, $this->destinationPath);
    }

    public function cleanupAfterFailure(Throwable $original): never
    {
        try {
            $this->filesystem->remove($this->stagingPath);
        } catch (Throwable $cleanupException) {
            throw FilesystemException::cleanupFailed($this->stagingPath, $cleanupException, $original);
        }

        throw $original;
    }
}
