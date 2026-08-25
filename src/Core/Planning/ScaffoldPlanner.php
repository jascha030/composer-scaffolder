<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Planning;

use Jascha030\Scaffolder\Core\Exception\PlanningException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Operation\FileOperation;

use function dirname;
use function in_array;
use function str_contains;

final class ScaffoldPlanner
{
    public function plan(Manifest $manifest, string $payloadPath, string $destination): ScaffoldPlan
    {
        $payloadRealPath = realpath($payloadPath);

        if (false === $payloadRealPath || ! is_dir($payloadRealPath)) {
            throw PlanningException::missingSource($payloadPath);
        }

        $destinationPath = $this->normalizeDestination($destination);
        $targets         = [];
        $operations      = [];

        foreach ($manifest->files as $file) {
            $this->assertRelativePath($file->source, PlanningException::absoluteSource(...), PlanningException::traversalSource(...));
            $this->assertRelativePath($file->target, PlanningException::absoluteTarget(...), PlanningException::traversalTarget(...));

            $sourcePath = Path::join($payloadRealPath, $file->source);
            $targetPath = Path::join($destinationPath, $file->target);

            $this->assertSourceInsidePayload($sourcePath, $payloadRealPath);
            $this->assertTargetAvailable($file->target, $targets);

            $operations[] = new FileOperation($sourcePath, $targetPath, $file->mode);
            $targets[]    = $file->target;
        }

        return new ScaffoldPlan($destinationPath, $operations);
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

    private function normalizeDestination(string $destination): string
    {
        $destination = rtrim($destination, '/\\');

        if ('' === $destination) {
            throw PlanningException::invalidDestination($destination);
        }

        $existing = realpath($destination);
        if (false !== $existing) {
            return $existing;
        }

        $parent = realpath(dirname($destination));
        if (false === $parent) {
            $parent = dirname($destination);
        }

        return Path::join($parent, basename($destination));
    }

    private function assertSourceInsidePayload(string $sourcePath, string $payloadRealPath): void
    {
        $realSource = realpath($sourcePath);

        if (false === $realSource || ! is_file($realSource)) {
            throw PlanningException::missingSource($sourcePath);
        }

        if (! str_starts_with($realSource, rtrim($payloadRealPath, '/\\') . '/')) {
            throw PlanningException::sourceOutsidePayload($sourcePath);
        }
    }

    /**
     * @param list<string> $targets
     */
    private function assertTargetAvailable(string $target, array $targets): void
    {
        foreach ($targets as $existing) {
            if ($target === $existing) {
                throw PlanningException::duplicateTarget($target);
            }

            if (str_starts_with($target, $existing . '/') || str_starts_with($existing, $target . '/')) {
                throw PlanningException::targetConflict($existing, $target);
            }
        }
    }
}
