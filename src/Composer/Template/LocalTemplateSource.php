<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Composer\Template;

use Jascha030\Scaffolder\Core\Exception\InvalidTemplateException;
use Jascha030\Scaffolder\Core\Manifest\Manifest;
use Jascha030\Scaffolder\Core\Planning\Path;
use Jascha030\Scaffolder\Core\Template\TemplatePackage;

use function array_keys;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function trim;

use const JSON_ERROR_NONE;

final class LocalTemplateSource
{
    private const EXPECTED_TYPE = 'jascha030-scaffold-template';
    private const EXTRA_KEY = 'jascha030-scaffold';
    private const METADATA_FIELDS = ['schema', 'manifest', 'payload'];

    public function load(string $path): TemplatePackage
    {
        $realRoot = realpath($path);

        if (false === $realRoot || ! is_dir($realRoot)) {
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

        if (($data['type'] ?? null) !== self::EXPECTED_TYPE) {
            throw InvalidTemplateException::invalidRootType(self::EXPECTED_TYPE);
        }

        $metadata = $this->metadata($data);
        $manifest = $this->resolvePath($realRoot, $metadata['manifest'], 'manifest');
        $payload  = $this->resolvePath($realRoot, $metadata['payload'], 'payload');

        if (! is_file($manifest)) {
            throw InvalidTemplateException::missingManifest($manifest);
        }

        if (! is_dir($payload)) {
            throw InvalidTemplateException::missingPayload($payload);
        }

        return new TemplatePackage($manifest, $payload);
    }

    /**
     * @param array<mixed, mixed> $data
     * @return array{schema: int, manifest: string, payload: string}
     */
    private function metadata(array $data): array
    {
        $metadata = $data['extra'][self::EXTRA_KEY] ?? null;

        if (! isset($data['extra']) || ! is_array($data['extra']) || null === $metadata) {
            throw InvalidTemplateException::missingExtra(self::EXTRA_KEY);
        }

        if (! is_array($metadata)) {
            throw InvalidTemplateException::invalidExtra(self::EXTRA_KEY);
        }

        foreach (array_keys($metadata) as $field) {
            if (! is_string($field) || ! in_array($field, self::METADATA_FIELDS, true)) {
                throw InvalidTemplateException::unexpectedField((string) $field);
            }
        }

        $schema = $metadata['schema'] ?? null;
        if (! is_int($schema) || Manifest::SUPPORTED_SCHEMA !== $schema) {
            throw InvalidTemplateException::unsupportedSchema(is_int($schema) ? $schema : 0, Manifest::SUPPORTED_SCHEMA);
        }

        $manifest = $metadata['manifest'] ?? null;
        $payload  = $metadata['payload'] ?? null;

        if (! is_string($manifest) || '' === trim($manifest)) {
            throw InvalidTemplateException::missingField('manifest');
        }

        if (! is_string($payload) || '' === trim($payload)) {
            throw InvalidTemplateException::missingField('payload');
        }

        return ['schema' => $schema, 'manifest' => $manifest, 'payload' => $payload];
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

        return false === $real ? $path : $real;
    }
}
