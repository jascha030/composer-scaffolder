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

namespace Jascha030\Scaffolder\Core\Planning;

final class Path
{
    public static function isAbsolute(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        if ('/' === $path[0] || '\\' === $path[0]) {
            return true;
        }

        return 1 === preg_match('~^[A-Za-z]:[/\\\]~', $path);
    }

    public static function containsTraversal(string $path): bool
    {
        $parts = explode('/', str_replace('\\', '/', $path));

        foreach ($parts as $part) {
            if ('..' === $part) {
                return true;
            }
        }

        return false;
    }

    public static function join(string $base, string $relative): string
    {
        return rtrim($base, '/\\') . '/' . ltrim($relative, '/\\');
    }
}
