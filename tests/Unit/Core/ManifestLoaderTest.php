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

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;
use Jascha030\Scaffolder\Core\Manifest\ManifestLoader;
use Jascha030\Scaffolder\Core\Operation\OperationMode;
use Jascha030\Scaffolder\Core\Question\TextQuestion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const JSON_PRETTY_PRINT;

/**
 * @internal
 */
#[CoversClass(ManifestLoader::class)]
final class ManifestLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/manifest-loader-test-' . uniqid();
        mkdir($this->tempDir, 0o700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function itLoadsAValidManifest(): void
    {
        $path = $this->writeManifest([
            'schema'    => 1,
            'questions' => [
                [
                    'key'        => 'package.name',
                    'type'       => 'text',
                    'prompt'     => 'Package name',
                    'required'   => true,
                    'validation' => [
                        'pattern' => '^[a-z]+/[a-z]+$',
                    ],
                ],
            ],
            'files' => [
                [
                    'source' => 'composer.json.stub',
                    'target' => 'composer.json',
                    'mode'   => 'render',
                ],
            ],
        ]);

        $manifest = (new ManifestLoader())->load($path);

        self::assertSame(1, $manifest->schema);
        self::assertCount(1, $manifest->questions);
        self::assertCount(1, $manifest->files);

        $question = $manifest->questions[0];
        self::assertInstanceOf(TextQuestion::class, $question);
        self::assertSame('package.name', $question->key);
        self::assertTrue($question->required);
        self::assertSame('^[a-z]+/[a-z]+$', $question->pattern);

        $file = $manifest->files[0];
        self::assertSame('composer.json.stub', $file->source);
        self::assertSame(OperationMode::Render, $file->mode);
    }

    #[Test]
    public function itRejectsInvalidJson(): void
    {
        $path = $this->tempDir . '/manifest.json';
        file_put_contents($path, '{not json');

        $this->expectException(InvalidManifestException::class);

        (new ManifestLoader())->load($path);
    }

    #[Test]
    public function itRejectsUnsupportedSchema(): void
    {
        $path = $this->writeManifest(['schema' => 2]);

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('Unsupported manifest schema');

        (new ManifestLoader())->load($path);
    }

    #[Test]
    public function itRejectsMissingQuestionKey(): void
    {
        $path = $this->writeManifest([
            'schema'    => 1,
            'questions' => [['type' => 'text', 'prompt' => 'Missing key']],
        ]);

        $this->expectException(InvalidManifestException::class);

        (new ManifestLoader())->load($path);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeManifest(array $data): string
    {
        $path = $this->tempDir . '/manifest.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
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
