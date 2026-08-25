<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Answer\AnswerResolver;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Contract\GeneratedProjectValidator;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
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
use function random_bytes;
use function strlen;

final class Scaffolder
{
    public function __construct(
        private readonly AnswerProvider $answerProvider,
        private readonly ScaffoldPlanner $planner,
        private readonly GeneratedProjectValidator $projectValidator,
        private readonly AnswerResolver $answerResolver = new AnswerResolver(),
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
        $plan    = $this->planner->plan($manifest, $template->payloadPath, $destination);
        $answers = $this->answerResolver->resolve($manifest, $predefined, $this->answerProvider);

        $this->assertDestinationAvailable($plan->destination, $force);

        if ($dryRun) {
            return $plan;
        }

        $staging = $this->createStagingDirectory($plan->destination);

        try {
            $this->executePlan($plan, $staging, $answers);
            $this->projectValidator->validate($staging);
            $this->finalizeDestination($staging, $plan->destination, $force);
        } catch (Throwable $exception) {
            try {
                $this->removeDirectory($staging);
            } catch (Throwable $cleanupException) {
                throw FilesystemException::cleanupFailed($staging, $cleanupException, $exception);
            }

            throw $exception;
        }

        return $plan;
    }

    private function assertDestinationAvailable(string $destination, bool $force): void
    {
        if (! file_exists($destination) && ! is_link($destination)) {
            return;
        }

        if (is_link($destination) || ! is_dir($destination) || ! $this->isDirectoryEmpty($destination)) {
            throw FilesystemException::destinationExists($destination);
        }

        if (! $force) {
            throw FilesystemException::emptyDestinationRequiresForce($destination);
        }
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

        $staging = $parent . '/.' . basename($destination) . '.scaffold.' . bin2hex(random_bytes(12));

        if (! @mkdir($staging, 0o700) || false === ($realStaging = realpath($staging))) {
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

        $rendered = str_ends_with($target, '.json') ? $renderer->renderJson($contents) : $renderer->render($contents);

        if (false === @file_put_contents($target, $rendered)) {
            throw FilesystemException::cannotWriteFile($target);
        }

        $this->copyPermissions($operation->source, $target);
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

        if (false !== $permissions && ! @chmod($target, $permissions & 0o7777)) {
            throw FilesystemException::cannotWriteFile($target);
        }
    }

    private function finalizeDestination(string $staging, string $destination, bool $force): void
    {
        if (file_exists($destination) || is_link($destination)) {
            $this->assertDestinationAvailable($destination, $force);
            $this->removeDirectory($destination);
        }

        if (! @rename($staging, $destination)) {
            throw FilesystemException::cannotMove($staging, $destination);
        }
    }

    private function removeDirectory(string $path): void
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
