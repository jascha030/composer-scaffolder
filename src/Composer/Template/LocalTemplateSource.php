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

namespace Jascha030\Scaffolder\Composer\Template;

use Jascha030\Scaffolder\Core\Exception\InvalidTemplateException;
use Jascha030\Scaffolder\Core\Planning\Path;
use Jascha030\Scaffolder\Core\Template\TemplatePackage;

use function is_array;
use function is_int;
use function is_string;

use const JSON_ERROR_NONE;

final class LocalTemplateSource
{
    private const SUPPORTED_SCHEMA = 1;

    private string $expectedType;

    private string $extraKey;

    public function __construct()
    {
        $this->expectedType = 'jascha030-scaffold-template';
        $this->extraKey     = 'jascha030-scaffold';
    }

    public function load(string $path): TemplatePackage
    {
        $realRoot = realpath($path);

        if (false === $realRoot) {
            throw InvalidTemplateException::missingComposerJson($path);
        }

        $composerJsonPath = $realRoot . '/composer.json';

        if (! is_file($composerJsonPath)) {
            throw InvalidTemplateException::missingComposerJson($realRoot);
        }

        $contents = @file_get_contents($composerJsonPath);

        if (false === $contents) {
            throw InvalidTemplateException::invalidJson($composerJsonPath, 'Unable to read file.');
        }

        $data = json_decode(trim($contents), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidTemplateException::invalidJson($composerJsonPath, json_last_error_msg());
        }

        if (! is_array($data)) {
            throw InvalidTemplateException::invalidJson($composerJsonPath, 'Root value must be an object.');
        }

        $this->validateType($data);
        $metadata = $this->extractMetadata($data, $realRoot);

        $manifestPath = $this->resolvePath($realRoot, $metadata['manifest'], 'manifest');
        $payloadPath  = $this->resolvePath($realRoot, $metadata['payload'], 'payload');

        if (! is_file($manifestPath)) {
            throw InvalidTemplateException::missingManifest($manifestPath);
        }

        if (! is_dir($payloadPath)) {
            throw InvalidTemplateException::missingPayload($payloadPath);
        }

        return new TemplatePackage($manifestPath, $payloadPath);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function validateType(array $data): void
    {
        if (! isset($data['type']) || $data['type'] !== $this->expectedType) {
            throw InvalidTemplateException::invalidRootType($this->expectedType);
        }
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array{schema: int, manifest: string, payload: string}
     */
    private function extractMetadata(array $data, string $root): array
    {
        if (! isset($data['extra']) || ! is_array($data['extra']) || ! isset($data['extra'][$this->extraKey])) {
            throw InvalidTemplateException::missingExtra($this->extraKey);
        }

        $metadata = $data['extra'][$this->extraKey];

        if (! is_array($metadata)) {
            throw InvalidTemplateException::invalidExtra($this->extraKey);
        }

        $schema   = $metadata['schema'] ?? null;
        $manifest = $metadata['manifest'] ?? null;
        $payload  = $metadata['payload'] ?? null;

        if (! is_int($schema)) {
            throw InvalidTemplateException::unsupportedSchema(0, self::SUPPORTED_SCHEMA);
        }

        if (self::SUPPORTED_SCHEMA !== $schema) {
            throw InvalidTemplateException::unsupportedSchema($schema, self::SUPPORTED_SCHEMA);
        }

        if (! is_string($manifest) || '' === $manifest) {
            throw InvalidTemplateException::missingField('manifest');
        }

        if (! is_string($payload) || '' === $payload) {
            throw InvalidTemplateException::missingField('payload');
        }

        return [
            'schema'   => $schema,
            'manifest' => $manifest,
            'payload'  => $payload,
        ];
    }

    private function resolvePath(string $root, string $relative, string $key): string
    {
        if (Path::isAbsolute($relative)) {
            throw InvalidTemplateException::absolutePath($key);
        }

        if (Path::containsTraversal($relative)) {
            throw InvalidTemplateException::unsafePath($key);
        }

        $path = Path::join($root, $relative);
        $real = realpath($path);

        if (false !== $real && ! str_starts_with($real, rtrim($root, '/\\') . '/')) {
            throw InvalidTemplateException::symlinkEscape($key);
        }

        return $path;
    }
}
