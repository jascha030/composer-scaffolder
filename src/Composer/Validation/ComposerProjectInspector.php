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
use Throwable;

final class ComposerProjectInspector implements GeneratedProjectInspector
{
    public function hasComposerJson(string $directory): bool
    {
        return is_file($directory . '/composer.json');
    }

    public function composerJsonValidationError(string $directory): ?string
    {
        $path = $directory . '/composer.json';

        try {
            (new JsonFile($path))->validateSchema(JsonFile::LAX_SCHEMA);
        } catch (JsonValidationException $exception) {
            return implode('; ', $exception->getErrors());
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
