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

namespace Jascha030\Scaffolder\Core\Validation;

use function preg_match;
use function str_replace;

final class RegularExpression
{
    public static function compile(string $pattern): string
    {
        return '~' . str_replace('~', '\~', $pattern) . '~';
    }

    public static function isValidPattern(string $pattern): bool
    {
        return false !== @preg_match(self::compile($pattern), '');
    }
}
