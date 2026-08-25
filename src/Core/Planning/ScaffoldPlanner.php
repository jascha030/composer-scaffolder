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

namespace Jascha030\Scaffolder\Core\Planning;

use Jascha030\Scaffolder\Core\Exception\PlanningException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Operation\FileOperation;

use function in_array;

final class ScaffoldPlanner
{
    public function plan(
        Manifest $manifest,
        string $payloadPath,
        string $destination,
    ): ScaffoldPlan {
        $payloadRealPath     = realpath($payloadPath);
        $destinationRealPath = realpath($destination) ?: rtrim($destination, '/\\');

        if (false === $payloadRealPath) {
            throw PlanningException::missingSource($payloadPath);
        }

        $targets    = [];
        $operations = [];

        foreach ($manifest->files as $file) {
            $this->assertRelativePath($file->source, PlanningException::absoluteSource(...), PlanningException::traversalSource(...));
            $this->assertRelativePath($file->target, PlanningException::absoluteTarget(...), PlanningException::traversalTarget(...));

            $sourcePath = Path::join($payloadRealPath, $file->source);
            $targetPath = Path::join($destinationRealPath, $file->target);

            $this->assertSourceExists($sourcePath);
            $this->assertSourceInsidePayload($sourcePath, $payloadRealPath);
            $this->assertTargetUnique($file->target, $targets);

            $operations[] = new FileOperation($sourcePath, $targetPath, $file->mode);
            $targets[]    = $file->target;
        }

        return new ScaffoldPlan($destinationRealPath, $operations);
    }

    /**
     * @param callable(string): PlanningException $absoluteException
     * @param callable(string): PlanningException $traversalException
     */
    private function assertRelativePath(string $path, callable $absoluteException, callable $traversalException): void
    {
        if (Path::isAbsolute($path)) {
            throw $absoluteException($path);
        }

        if (Path::containsTraversal($path)) {
            throw $traversalException($path);
        }
    }

    private function assertSourceExists(string $sourcePath): void
    {
        if (! is_file($sourcePath)) {
            throw PlanningException::missingSource($sourcePath);
        }
    }

    private function assertSourceInsidePayload(string $sourcePath, string $payloadRealPath): void
    {
        $realSource = realpath($sourcePath);

        if (false === $realSource) {
            throw PlanningException::missingSource($sourcePath);
        }

        if (! str_starts_with($realSource, rtrim($payloadRealPath, '/\\') . '/')) {
            throw PlanningException::sourceOutsidePayload($sourcePath);
        }

        if (is_link($sourcePath)) {
            throw PlanningException::symlinkEscape($sourcePath);
        }
    }

    /**
     * @param list<string> $targets
     */
    private function assertTargetUnique(string $target, array $targets): void
    {
        if (in_array($target, $targets, true)) {
            throw PlanningException::duplicateTarget($target);
        }
    }
}
