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

namespace Jascha030\Scaffolder\Core\Manifest;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;

use function is_array;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function trim;

use const JSON_ERROR_NONE;

final class ManifestLoader
{
    public function __construct(private readonly ManifestParser $parser = new ManifestParser())
    {
    }

    public function load(string $path): Manifest
    {
        $contents = @file_get_contents($path);

        if (false === $contents) {
            throw InvalidManifestException::invalidJson($path, 'Unable to read file.');
        }

        $data = json_decode(trim($contents), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidManifestException::invalidJson($path, json_last_error_msg());
        }

        if (! is_array($data)) {
            throw InvalidManifestException::notAnObject($path);
        }

        return $this->parser->parse($data);
    }
}
