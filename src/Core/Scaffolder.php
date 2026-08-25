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

namespace Jascha030\Scaffolder\Core;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlan;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use Jascha030\Scaffolder\Core\Rendering\TemplateRenderer;
use Jascha030\Scaffolder\Core\Template\TemplatePackage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

use function dirname;
use function is_object;
use function strlen;

use const JSON_ERROR_NONE;

final class Scaffolder
{
    public function __construct(
        private readonly AnswerProvider $answerProvider,
        private readonly ScaffoldPlanner $planner,
    ) {
    }

    public function scaffold(
        TemplatePackage $template,
        Manifest $manifest,
        string $destination,
        AnswerBag $predefined,
        bool $dryRun = false,
        bool $force = false,
    ): ScaffoldPlan {
        $answers = $this->answerProvider->collect($manifest, $predefined);
        $plan    = $this->planner->plan($manifest, $template->payloadPath, $destination);

        if ($dryRun) {
            return $plan;
        }

        $this->prepareDestinationDirectory($plan->destination, $force);
        $staging = $this->createStagingDirectory($plan->destination);

        try {
            $this->executePlan($plan, $staging, $answers);
            $this->validateGeneratedProject($staging);
            $this->finalizeDestination($staging, $plan->destination);
        } catch (Throwable $exception) {
            $this->removeDirectory($staging);

            throw $exception;
        }

        return $plan;
    }

    private function prepareDestinationDirectory(string $destination, bool $force): void
    {
        if (! file_exists($destination)) {
            return;
        }

        if (! is_dir($destination)) {
            throw FilesystemException::destinationExists($destination);
        }

        if (! $this->isDirectoryEmpty($destination)) {
            throw FilesystemException::destinationExists($destination);
        }

        if (! $force) {
            throw FilesystemException::emptyDestinationRequiresForce($destination);
        }

        $this->removeDirectory($destination);
    }

    private function isDirectoryEmpty(string $path): bool
    {
        $entries = @scandir($path);

        if (false === $entries) {
            return false;
        }

        return [] === array_diff($entries, ['.', '..']);
    }

    private function createStagingDirectory(string $destination): string
    {
        $parent = dirname($destination);

        if (! is_dir($parent) && ! @mkdir($parent, 0o755, true) && ! is_dir($parent)) {
            throw FilesystemException::cannotCreateDirectory($parent);
        }

        $staging = $parent . '/.' . basename($destination) . '.scaffold.' . uniqid('', true);

        if (! @mkdir($staging, 0o755, true) && ! is_dir($staging)) {
            throw FilesystemException::cannotCreateDirectory($staging);
        }

        $realStaging = realpath($staging);

        if (false === $realStaging) {
            throw FilesystemException::cannotCreateDirectory($staging);
        }

        return $realStaging;
    }

    private function executePlan(ScaffoldPlan $plan, string $staging, AnswerBag $answers): void
    {
        $renderer = new TemplateRenderer($answers);

        foreach ($plan->operations as $operation) {
            $target = $this->resolveStagingTarget($operation, $plan->destination, $staging);
            $this->createParentDirectory($target);

            match ($operation->mode) {
                OperationMode::Render => $this->renderFile($operation, $target, $renderer),
                OperationMode::Copy   => $this->copyFile($operation, $target),
            };
        }
    }

    private function resolveStagingTarget(FileOperation $operation, string $destination, string $staging): string
    {
        $prefix = rtrim($destination, '/\\') . '/';

        if (! str_starts_with($operation->target, $prefix)) {
            throw FilesystemException::cannotWriteFile($operation->target);
        }

        return $staging . '/' . substr($operation->target, strlen($prefix));
    }

    private function createParentDirectory(string $path): void
    {
        $parent = dirname($path);

        if (! is_dir($parent) && ! @mkdir($parent, 0o755, true) && ! is_dir($parent)) {
            throw FilesystemException::cannotCreateDirectory($parent);
        }
    }

    private function renderFile(FileOperation $operation, string $target, TemplateRenderer $renderer): void
    {
        $contents = @file_get_contents($operation->source);

        if (false === $contents) {
            throw FilesystemException::cannotReadFile($operation->source);
        }

        $rendered = $this->isJsonTarget($target) ? $renderer->renderJson($contents) : $renderer->render($contents);

        if (false === @file_put_contents($target, $rendered)) {
            throw FilesystemException::cannotWriteFile($target);
        }

        $this->copyPermissions($operation->source, $target);
    }

    private function isJsonTarget(string $target): bool
    {
        return str_ends_with($target, '.json');
    }

    private function copyFile(FileOperation $operation, string $target): void
    {
        if (! @copy($operation->source, $target)) {
            throw FilesystemException::cannotWriteFile($target);
        }

        $this->copyPermissions($operation->source, $target);
    }

    private function copyPermissions(string $source, string $target): void
    {
        $permissions = @fileperms($source);

        if (false !== $permissions) {
            @chmod($target, $permissions);
        }
    }

    private function validateGeneratedProject(string $staging): void
    {
        $composerJson = $staging . '/composer.json';

        if (! is_file($composerJson)) {
            throw InvalidManifestException::notAnObject($composerJson);
        }

        $contents = @file_get_contents($composerJson);

        if (false === $contents) {
            throw InvalidManifestException::invalidJson($composerJson, 'Unable to read file.');
        }

        $data = json_decode(trim($contents));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidManifestException::invalidJson($composerJson, json_last_error_msg());
        }

        if (! is_object($data)) {
            throw InvalidManifestException::notAnObject($composerJson);
        }
    }

    private function finalizeDestination(string $staging, string $destination): void
    {
        if (! @rename($staging, $destination)) {
            throw FilesystemException::cannotMove($staging, $destination);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            @unlink($path);

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

            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
