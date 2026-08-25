<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Validation;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;

use function preg_match;
use function str_replace;

final class RegularExpression
{
    public static function compile(string $pattern): string
    {
        return '~' . str_replace('~', '\\~', $pattern) . '~';
    }

    public static function assertValid(string $pattern): void
    {
        if (false === @preg_match(self::compile($pattern), '')) {
            throw InvalidManifestException::invalidPattern($pattern);
        }
    }
}
