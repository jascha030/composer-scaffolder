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

namespace Jascha030\Scaffolder\Composer\Validation;

use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Jascha030\Scaffolder\Core\Contract\GeneratedProjectInspector;
use Jascha030\Scaffolder\Core\Exception\GeneratedProjectValidationException;
use Throwable;

final class ComposerProjectInspector implements GeneratedProjectInspector
{
    public function assertValidProject(string $directory): void
    {
        $path = $directory . '/composer.json';

        if (! is_file($path)) {
            throw GeneratedProjectValidationException::missingComposerJson($path);
        }

        try {
            (new JsonFile($path))->validateSchema(JsonFile::LAX_SCHEMA);
        } catch (JsonValidationException $exception) {
            throw GeneratedProjectValidationException::invalidAt($path, implode('; ', $exception->getErrors()));
        } catch (Throwable $exception) {
            throw GeneratedProjectValidationException::invalidAt($path, $exception->getMessage());
        }
    }
}
