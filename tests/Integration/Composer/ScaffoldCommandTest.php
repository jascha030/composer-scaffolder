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

use Jascha030\Scaffolder\Composer\Bootstrap\ScaffolderFactory;
use Jascha030\Scaffolder\Composer\Command\ScaffoldCommand;
use Jascha030\Scaffolder\Composer\Template\LocalTemplateSource;
use Jascha030\Scaffolder\Core\Manifest\ManifestLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Composer\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;
use function file_put_contents;
use function mkdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @internal
 */
#[CoversClass(ScaffoldCommand::class)]
final class ScaffoldCommandTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/composer-scaffolder-command-' . uniqid();
        mkdir($this->root, 0o700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    #[Test]
    public function itRejectsAWrongTemplateType(): void
    {
        $template = $this->root . '/template';
        mkdir($template . '/payload', 0o700, true);
        file_put_contents($template . '/composer.json', '{"name":"acme/template","type":"library","extra":{"jascha030-scaffold":{"schema":1,"manifest":"manifest.json","payload":"payload"}}}');
        file_put_contents($template . '/manifest.json', '{"schema":1,"questions":[],"files":[]}');

        $tester = $this->commandTester();
        $tester->execute(['template' => $template, 'directory' => $this->root . '/out']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Template package type must be exactly', $tester->getDisplay());
    }

    #[Test]
    public function itRejectsMissingTemplateMetadata(): void
    {
        $template = $this->root . '/template';
        mkdir($template, 0o700, true);
        file_put_contents($template . '/composer.json', '{"name":"acme/template","type":"jascha030-scaffold-template"}');

        $tester = $this->commandTester();
        $tester->execute(['template' => $template, 'directory' => $this->root . '/out']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('must contain "extra.jascha030-scaffold" metadata', $tester->getDisplay());
    }

    #[Test]
    public function itRejectsUnsupportedSchema(): void
    {
        $template = $this->root . '/template';
        mkdir($template . '/payload', 0o700, true);
        file_put_contents($template . '/composer.json', '{"name":"acme/template","type":"jascha030-scaffold-template","extra":{"jascha030-scaffold":{"schema":2,"manifest":"manifest.json","payload":"payload"}}}');

        $tester = $this->commandTester();
        $tester->execute(['template' => $template, 'directory' => $this->root . '/out']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unsupported scaffold schema', $tester->getDisplay());
    }

    #[Test]
    public function itRejectsUnsafeTargetPaths(): void
    {
        $template = $this->root . '/template';
        mkdir($template . '/payload', 0o700, true);
        file_put_contents($template . '/composer.json', '{"name":"acme/template","type":"jascha030-scaffold-template","extra":{"jascha030-scaffold":{"schema":1,"manifest":"manifest.json","payload":"payload"}}}');
        file_put_contents($template . '/manifest.json', '{"schema":1,"questions":[],"files":[{"source":"file.txt","target":"../escape.txt","mode":"copy"}]}');
        file_put_contents($template . '/payload/file.txt', 'payload');

        $tester = $this->commandTester();
        $tester->execute(['template' => $template, 'directory' => $this->root . '/out']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Target path', $tester->getDisplay());
    }

    #[Test]
    public function itGeneratesTheMinimalFixtureProject(): void
    {
        $template = $this->createValidTemplate();
        $output   = $this->root . '/generated';

        $tester = $this->commandTester();
        $tester->setInputs(['acme/demo']);
        $tester->execute([
            'template' => $template,
            'directory' => $output,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($output . '/composer.json');
        self::assertFileExists($output . '/README.md');
        self::assertStringContainsString('acme/demo', (string) file_get_contents($output . '/composer.json'));
        self::assertStringContainsString('Generated project in', $tester->getDisplay());
    }

    #[Test]
    public function itPerformsADryRunWithoutWritingFiles(): void
    {
        $template = $this->createValidTemplate();
        $output   = $this->root . '/dry-run-output';

        $tester = $this->commandTester();
        $tester->execute([
            'template' => $template,
            'directory' => $output,
            '--set' => ['package.name=acme/demo'],
            '--dry-run' => true,
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertDirectoryDoesNotExist($output);
        self::assertStringContainsString('Planned operations:', $tester->getDisplay());
        self::assertStringContainsString('composer.json.stub', $tester->getDisplay());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new ScaffoldCommand(
            new LocalTemplateSource(),
            new ManifestLoader(),
            new ScaffolderFactory(),
        ));

        return new CommandTester($application->find('scaffold'));
    }

    private function createValidTemplate(): string
    {
        $template = $this->root . '/template-valid';
        mkdir($template . '/payload/src', 0o700, true);
        file_put_contents(
            $template . '/composer.json',
            sprintf(
                '{"name":"acme/template","type":"jascha030-scaffold-template","extra":{"jascha030-scaffold":{"schema":1,"manifest":"manifest.json","payload":"payload"}}}'
            )
        );
        file_put_contents(
            $template . '/manifest.json',
            '{"schema":1,"questions":[{"key":"package.name","type":"text","prompt":"Package name","required":true}],"files":[{"source":"composer.json.stub","target":"composer.json","mode":"render"},{"source":"README.md.stub","target":"README.md","mode":"render"}]}'
        );
        file_put_contents($template . '/payload/composer.json.stub', '{"name":"{{ package.name }}"}');
        file_put_contents($template . '/payload/README.md.stub', '# {{ package.name }}');

        return $template;
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
