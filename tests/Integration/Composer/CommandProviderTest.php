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

namespace Jascha030\Scaffolder\Tests\Integration\Composer;

use Jascha030\Scaffolder\Composer\Command\ScaffoldCommand;
use Jascha030\Scaffolder\Composer\CommandProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandProvider::class)]
final class CommandProviderTest extends TestCase
{
    #[Test]
    public function itProvidesTheScaffoldCommand(): void
    {
        $commands = (new CommandProvider())->getCommands();

        self::assertCount(1, $commands);
        self::assertInstanceOf(ScaffoldCommand::class, $commands[0]);
    }
}
