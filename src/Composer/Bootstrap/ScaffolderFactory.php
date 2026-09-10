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

namespace Jascha030\Scaffolder\Composer\Bootstrap;

use Jascha030\Scaffolder\Composer\Validation\ComposerProjectInspector;
use Jascha030\Scaffolder\Core\Answer\AnswerResolver;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Execution\PlanExecutor;
use Jascha030\Scaffolder\Core\Filesystem\NativeProjectFilesystem;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use Jascha030\Scaffolder\Core\Scaffolder;

final class ScaffolderFactory
{
    public function create(AnswerProvider $answerProvider): Scaffolder
    {
        $filesystem = new NativeProjectFilesystem();

        return new Scaffolder(
            $answerProvider,
            new ScaffoldPlanner(),
            new ComposerProjectInspector(),
            new AnswerResolver(),
            $filesystem,
            new PlanExecutor($filesystem),
        );
    }
}
