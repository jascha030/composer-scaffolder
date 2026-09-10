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
use Jascha030\Scaffolder\Core\Answer\AnswerResolver;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Contract\GeneratedProjectInspector;
use Jascha030\Scaffolder\Core\Contract\ProjectFilesystem;
use Jascha030\Scaffolder\Core\Execution\PlanExecutor;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlan;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use Jascha030\Scaffolder\Core\Template\TemplatePackage;
use Jascha030\Scaffolder\Core\Workspace\StagingWorkspace;
use Throwable;

final class Scaffolder
{
    public function __construct(
        private readonly AnswerProvider $answerProvider,
        private readonly ScaffoldPlanner $planner,
        private readonly GeneratedProjectInspector $projectInspector,
        private readonly AnswerResolver $answerResolver,
        private readonly ProjectFilesystem $filesystem,
        private readonly PlanExecutor $planExecutor,
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

        $this->filesystem->assertDestinationAvailable($plan->destination, $force);

        if ($dryRun) {
            return $plan;
        }

        $workspace = StagingWorkspace::create(
            $plan->destination,
            $this->filesystem,
        );

        try {
            $this->planExecutor->execute($plan, $workspace->stagingPath(), $answers);
            $this->projectInspector->assertValidProject($workspace->stagingPath());
            $workspace->publish($force);
        } catch (Throwable $exception) {
            $workspace->cleanupAfterFailure($exception);
        }

        return $plan;
    }
}
