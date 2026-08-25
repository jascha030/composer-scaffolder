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

namespace Jascha030\Scaffolder\Core\Exception;

use function sprintf;

final class GeneratedProjectValidationException extends ScaffolderException
{
    public static function missingComposerJson(string $path): self
    {
        return new self(sprintf('Generated project "%s" does not contain a composer.json file.', $path));
    }

    public static function invalidAt(string $path, string $error): self
    {
        return new self(sprintf('Generated Composer manifest "%s" is invalid: %s', $path, $error));
    }
}
