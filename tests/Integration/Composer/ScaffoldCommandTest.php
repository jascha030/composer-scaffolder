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

use Composer\Composer;
use Composer\Console\Application as ComposerApplication;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Jascha030\Scaffolder\Composer\Command\ScaffoldCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use SplFileInfo;
use Symfony\Component\Console\Tester\ApplicationTester;

use function strlen;

/**
 * @internal
 */
#[CoversClass(ScaffoldCommand::class)]
final class ScaffoldCommandTest extends TestCase
{
    private string $destination;

    protected function setUp(): void
    {
        $this->destination = sys_get_temp_dir() . '/scaffold-command-' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->destination);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input): ApplicationTester
    {
        $composer = new Composer();
        $composer->setConfig(Factory::createConfig(new NullIO(), getcwd() ?: __DIR__));
        $composer->setPackage(new RootPackage('test/test', '1.0.0.0', '1.0.0'));
        $composer->setEventDispatcher(new EventDispatcher($composer, new NullIO()));

        $application = new ComposerApplication();
        $application->setAutoExit(false);

        $property = new ReflectionProperty($application, 'composer');
        $property->setValue($application, $composer);

        $application->add(new ScaffoldCommand());

        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'scaffold'] + $input);

        return $tester;
    }

    #[Test]
    public function itGeneratesTheMinimalFixtureProject(): void
    {
        $tester = $this->runCommand([
            'template'  => __DIR__ . '/../../Fixtures/Templates/minimal',
            'directory' => $this->destination,
            '--set'     => [
                'package.name=acme/generated-example',
                'project.namespace=Acme\GeneratedExample',
            ],
            '--no-interaction' => true,
        ]);

        self::assertSame(0, $tester->getStatusCode());

        self::assertDirectoryExists($this->destination);
        self::assertFileExists($this->destination . '/composer.json');
        self::assertFileExists($this->destination . '/README.md');
        self::assertFileExists($this->destination . '/src/Application.php');
        self::assertFileDoesNotExist($this->destination . '/scaffold.json');
        self::assertFileDoesNotExist($this->destination . '/template');

        $composerJson = json_decode(file_get_contents($this->destination . '/composer.json') ?: '', true);
        self::assertIsArray($composerJson);
        self::assertSame('acme/generated-example', $composerJson['name']);

        $expected = __DIR__ . '/../../Fixtures/Expected/minimal';
        self::assertDirectoriesAreEqual($expected, $this->destination);
    }

    #[Test]
    public function itPerformsADryRunWithoutWritingFiles(): void
    {
        $tester = $this->runCommand([
            'template'  => __DIR__ . '/../../Fixtures/Templates/minimal',
            'directory' => $this->destination,
            '--set'     => [
                'package.name=acme/generated-example',
                'project.namespace=Acme\GeneratedExample',
            ],
            '--dry-run'        => true,
            '--no-interaction' => true,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Planned operations:', $tester->getDisplay());
        self::assertDirectoryDoesNotExist($this->destination);
    }

    #[Test]
    public function itRejectsAWrongTemplateType(): void
    {
        $tester = $this->runCommand([
            'template'         => __DIR__ . '/../../Fixtures/Templates/invalid/wrong-type',
            'directory'        => $this->destination,
            '--no-interaction' => true,
        ]);

        self::assertSame(1, $tester->getStatusCode());
    }

    #[Test]
    public function itRejectsMissingTemplateMetadata(): void
    {
        $tester = $this->runCommand([
            'template'         => __DIR__ . '/../../Fixtures/Templates/invalid/missing-extra',
            'directory'        => $this->destination,
            '--no-interaction' => true,
        ]);

        self::assertSame(1, $tester->getStatusCode());
    }

    #[Test]
    public function itRejectsUnsupportedSchema(): void
    {
        $tester = $this->runCommand([
            'template'         => __DIR__ . '/../../Fixtures/Templates/invalid/unsupported-schema',
            'directory'        => $this->destination,
            '--no-interaction' => true,
        ]);

        self::assertSame(1, $tester->getStatusCode());
    }

    #[Test]
    public function itRejectsUnsafeTargetPaths(): void
    {
        $tester = $this->runCommand([
            'template'         => __DIR__ . '/../../Fixtures/Templates/invalid/unsafe-target',
            'directory'        => $this->destination,
            '--no-interaction' => true,
        ]);

        self::assertSame(1, $tester->getStatusCode());
    }

    private function assertDirectoriesAreEqual(string $expected, string $actual): void
    {
        $expectedFiles = $this->listRelativeFiles($expected);
        $actualFiles   = $this->listRelativeFiles($actual);

        self::assertSame($expectedFiles, $actualFiles);

        foreach ($expectedFiles as $file) {
            self::assertSame(
                file_get_contents($expected . '/' . $file),
                file_get_contents($actual . '/' . $file),
                "File {$file} differs.",
            );
        }
    }

    /**
     * @return list<string>
     */
    private function listRelativeFiles(string $directory): array
    {
        $files    = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            $files[] = substr($file->getPathname(), strlen($directory) + 1);
        }

        sort($files);

        return $files;
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
