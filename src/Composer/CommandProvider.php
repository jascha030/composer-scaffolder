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

namespace Jascha030\Scaffolder\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Jascha030\Scaffolder\Composer\Command\ScaffoldCommand;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [new ScaffoldCommand()];
    }
}
