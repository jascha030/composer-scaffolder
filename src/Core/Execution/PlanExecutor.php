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

namespace Jascha030\Scaffolder\Core\Execution;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Contract\ProjectFilesystem;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlan;
use Jascha030\Scaffolder\Core\Rendering\TemplateRenderer;

use function dirname;
use function strlen;

final class PlanExecutor
{
    public function __construct(private readonly ProjectFilesystem $filesystem)
    {
    }

    public function execute(ScaffoldPlan $plan, string $staging, AnswerBag $answers): void
    {
        $renderer = new TemplateRenderer($answers);

        foreach ($plan->operations as $operation) {
            $target = $this->resolveStagingTarget($operation, $plan->destination, $staging);
            $this->filesystem->ensureDirectory(dirname($target));

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

    private function renderFile(FileOperation $operation, string $target, TemplateRenderer $renderer): void
    {
        $contents = $this->filesystem->readFile($operation->source);
        $rendered = str_ends_with($target, '.json') ? $renderer->renderJson($contents) : $renderer->render($contents);

        $this->filesystem->writeFile($target, $rendered);
        $this->filesystem->copyPermissions($operation->source, $target);
    }

    private function copyFile(FileOperation $operation, string $target): void
    {
        $this->filesystem->copyFile($operation->source, $target);
        $this->filesystem->copyPermissions($operation->source, $target);
    }
}
