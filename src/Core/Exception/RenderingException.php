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

final class RenderingException extends ScaffolderException
{
    public static function missingToken(string $token): self
    {
        return new self(sprintf('Template references missing answer "%s".', $token));
    }

    public static function renderFailed(): self
    {
        return new self('Failed to render template content.');
    }
}
