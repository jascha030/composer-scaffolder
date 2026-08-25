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

namespace Jascha030\Scaffolder\Tests\Unit\Core;

use Jascha030\Scaffolder\Core\Exception\PlanningException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;
use function is_file;
use function uniqid;
use function unlink;

/**
 * @internal
 */
#[CoversClass(ScaffoldPlanner::class)]
final class ScaffoldPlannerTest extends TestCase
{
    private string $payload;

    private string $destination;

    private ScaffoldPlanner $planner;

    protected function setUp(): void
    {
        $this->payload     = sys_get_temp_dir() . '/planner-payload-' . uniqid();
        $this->destination = sys_get_temp_dir() . '/planner-dest-' . uniqid();
        $this->planner     = new ScaffoldPlanner();

        mkdir($this->payload . '/src', 0o700, true);
        file_put_contents($this->payload . '/composer.json.stub', '{}');
        file_put_contents($this->payload . '/src/Application.php.stub', '<?php');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->payload);
        $this->removeDirectory($this->destination);
    }

    #[Test]
    public function itBuildsAPlan(): void
    {
        $manifest = $this->manifest([
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
            new FileOperation('src/Application.php.stub', 'src/Application.php', OperationMode::Copy),
        ]);

        $plan = $this->planner->plan($manifest, $this->payload, $this->destination);

        self::assertCount(2, $plan->operations);
        self::assertStringEndsWith('composer.json', $plan->operations[0]->target);
        self::assertSame(OperationMode::Render, $plan->operations[0]->mode);
        self::assertSame(OperationMode::Copy, $plan->operations[1]->mode);
    }

    #[Test]
    public function itRejectsAbsoluteTargetPaths(): void
    {
        $manifest = $this->manifest([new FileOperation('composer.json.stub', '/etc/passwd', OperationMode::Copy)]);

        $this->expectException(PlanningException::class);
        $this->expectExceptionMessage('Target path "/etc/passwd" must be relative');

        $this->planner->plan($manifest, $this->payload, $this->destination);
    }

    #[Test]
    public function itRejectsTargetTraversal(): void
    {
        $manifest = $this->manifest([new FileOperation('composer.json.stub', '../escape.txt', OperationMode::Copy)]);

        $this->expectException(PlanningException::class);
        $this->expectExceptionMessage('Target path "../escape.txt" must not contain ".."');

        $this->planner->plan($manifest, $this->payload, $this->destination);
    }

    #[Test]
    public function itRejectsDuplicateTargets(): void
    {
        $manifest = $this->manifest([
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
            new FileOperation('src/Application.php.stub', 'composer.json', OperationMode::Copy),
        ]);

        $this->expectException(PlanningException::class);
        $this->expectExceptionMessage('Target path "composer.json" is defined more than once');

        $this->planner->plan($manifest, $this->payload, $this->destination);
    }

    #[Test]
    public function itRejectsMissingSourceFiles(): void
    {
        $manifest = $this->manifest([new FileOperation('missing.txt', 'missing.txt', OperationMode::Copy)]);

        $this->expectException(PlanningException::class);
        $this->expectExceptionMessage('Source file');

        $this->planner->plan($manifest, $this->payload, $this->destination);
    }

    #[Test]
    public function itRejectsSymlinksThatEscapePayload(): void
    {
        $target = dirname($this->payload) . '/planner-escape-' . uniqid() . '.txt';
        file_put_contents($target, 'secret');
        symlink($target, $this->payload . '/link.txt');

        $manifest = $this->manifest([new FileOperation('link.txt', 'link.txt', OperationMode::Copy)]);

        $this->expectException(PlanningException::class);
        $this->expectExceptionMessage('outside the template payload');

        try {
            $this->planner->plan($manifest, $this->payload, $this->destination);
        } finally {
            if (is_file($target)) {
                unlink($target);
            }
        }
    }

    /**
     * @param list<FileOperation> $files
     */
    private function manifest(array $files): Manifest
    {
        return new Manifest(1, [], $files);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $entryPath = $path . '/' . $entry;
            is_dir($entryPath) ? $this->removeDirectory($entryPath) : unlink($entryPath);
        }

        rmdir($path);
    }
}
