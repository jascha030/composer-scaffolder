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

final class InvalidAnswerException extends ScaffolderException
{
    public static function requiredMissing(string $key): self
    {
        return new self(sprintf('Required answer "%s" is missing.', $key));
    }

    public static function patternMismatch(string $key, string $value, string $pattern): self
    {
        return new self(sprintf('Answer "%s" with value "%s" does not match pattern "%s".', $key, $value, $pattern));
    }

    public static function duplicateKey(string $key): self
    {
        return new self(sprintf('Duplicate answer key "%s" provided.', $key));
    }

    public static function malformedSet(string $value): self
    {
        return new self(sprintf('Malformed "--set" value "%s". Expected "key=value".', $value));
    }
}
