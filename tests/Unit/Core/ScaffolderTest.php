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

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Answer\AnswerResolver;
use Jascha030\Scaffolder\Core\Contract\AnswerProvider;
use Jascha030\Scaffolder\Core\Contract\GeneratedProjectInspector;
use Jascha030\Scaffolder\Core\Execution\PlanExecutor;
use Jascha030\Scaffolder\Core\Exception\FilesystemException;
use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Exception\RenderingException;
use Jascha030\Scaffolder\Core\Filesystem\NativeProjectFilesystem;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use Jascha030\Scaffolder\Core\Question\TextQuestion;
use Jascha030\Scaffolder\Core\Scaffolder;
use Jascha030\Scaffolder\Core\Template\TemplatePackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * @internal
 */
#[CoversClass(Scaffolder::class)]
final class ScaffolderTest extends TestCase
{
    private string $payload;

    private string $destination;

    private Scaffolder $scaffolder;

    protected function setUp(): void
    {
        $this->payload     = sys_get_temp_dir() . '/scaffolder-payload-' . uniqid();
        $this->destination = sys_get_temp_dir() . '/scaffolder-dest-' . uniqid();
        $this->scaffolder  = $this->makeScaffolder($this->answerProvider([]));

        mkdir($this->payload . '/src', 0o700, true);
        file_put_contents($this->payload . '/composer.json.stub', '{"name":"{{ package.name }}"}');
        file_put_contents($this->payload . '/README.md.stub', '# {{ project.namespace }}');
        file_put_contents($this->payload . '/src/Application.php.stub', '<?php');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->payload);
        $this->removeDirectory($this->destination);
    }

    #[Test]
    public function itGeneratesAProjectWithPredefinedAnswers(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [
            new TextQuestion('package.name', 'Package name', null, true),
        ], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $scaffolder = $this->makeScaffolder($this->answerProvider(['package.name' => 'acme/demo']));
        $scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag(['package.name' => 'acme/demo']));

        self::assertFileExists($this->destination . '/composer.json');
        self::assertStringContainsString('acme/demo', file_get_contents($this->destination . '/composer.json') ?: '');
    }

    #[Test]
    public function itCopiesFilesWithoutRendering(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Copy),
        ]);

        $this->scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag([]));

        self::assertFileExists($this->destination . '/composer.json');
        self::assertStringContainsString('{{ package.name }}', file_get_contents($this->destination . '/composer.json') ?: '');
    }

    #[Test]
    public function itRejectsANonemptyDestination(): void
    {
        mkdir($this->destination, 0o700, true);
        file_put_contents($this->destination . '/existing.txt', 'existing');

        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('already exists and is not empty');

        $this->scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag([]));
    }

    #[Test]
    public function itAllowsReplacingAnEmptyDestinationWithForce(): void
    {
        mkdir($this->destination, 0o700, true);

        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Copy),
        ]);

        $this->scaffolder->scaffold(
            $template,
            $manifest,
            $this->destination,
            new AnswerBag([]),
            false,
            true,
        );

        self::assertFileExists($this->destination . '/composer.json');
    }

    #[Test]
    public function itCleansUpStagingOnFailure(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $scaffolder = $this->makeScaffolder($this->answerProvider([]));

        try {
            $scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag([]));
        } catch (RenderingException) {
        }

        self::assertDirectoryDoesNotExist($this->destination);
        self::assertSame([], glob(dirname($this->destination) . '/.' . basename($this->destination) . '.scaffold.*'));
    }

    #[Test]
    public function itDoesNotWriteFilesDuringDryRun(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $plan = $this->scaffolder->scaffold(
            $template,
            $manifest,
            $this->destination,
            new AnswerBag([]),
            true,
        );

        self::assertCount(1, $plan->operations);
        self::assertDirectoryDoesNotExist($this->destination);
    }

    #[Test]
    public function itRejectsMissingRequiredAnswers(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [
            new TextQuestion('package.name', 'Package name', null, true),
        ], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $this->expectException(InvalidAnswerException::class);
        $this->expectExceptionMessage('Required answer "package.name" is missing');

        $this->scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag([]));
    }

    #[Test]
    public function itValidatesAnswerPatterns(): void
    {
        $template = new TemplatePackage('', $this->payload);
        $manifest = new Manifest(1, [
            new TextQuestion('package.name', 'Package name', null, true, '^[a-z]+/[a-z]+$'),
        ], [
            new FileOperation('composer.json.stub', 'composer.json', OperationMode::Render),
        ]);

        $this->expectException(InvalidAnswerException::class);
        $this->expectExceptionMessage('does not match pattern');

        $this->scaffolder->scaffold($template, $manifest, $this->destination, new AnswerBag(['package.name' => 'Invalid']));
    }

    private function makeScaffolder(AnswerProvider $answerProvider): Scaffolder
    {
        $filesystem = new NativeProjectFilesystem();

        return new Scaffolder(
            $answerProvider,
            new ScaffoldPlanner(),
            $this->projectInspector(),
            new AnswerResolver(),
            $filesystem,
            new PlanExecutor($filesystem),
        );
    }

    private function projectInspector(): GeneratedProjectInspector
    {
        return new class implements GeneratedProjectInspector {
            public function assertValidProject(string $directory): void
            {
            }
        };
    }

    /**
     * @param array<string, string|null> $answers
     */
    private function answerProvider(array $answers): AnswerProvider
    {
        return new class ($answers) implements AnswerProvider {
            /**
             * @param array<string, string|null> $answers
             */
            public function __construct(private readonly array $answers)
            {
            }

            public function collect(Manifest $manifest, AnswerBag $predefinedAnswers): AnswerBag
            {
                $bag = new AnswerBag([]);

                foreach ($predefinedAnswers->all() as $key => $value) {
                    $bag = $bag->with($key, $value);
                }

                foreach ($this->answers as $key => $value) {
                    $bag = $bag->with($key, $value);
                }

                return $bag;
            }
        };
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
