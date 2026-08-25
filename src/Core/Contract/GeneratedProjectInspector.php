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

interface GeneratedProjectInspector
{
    public function hasComposerJson(string $directory): bool;

    public function composerJsonValidationError(string $directory): ?string;
}
