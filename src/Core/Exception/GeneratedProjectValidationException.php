<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Exception;

use function sprintf;

final class GeneratedProjectValidationException extends ScaffolderException
{
    /**
     * @param list<string> $errors
     */
    public static function invalid(string $path, array $errors): self
    {
        return new self(sprintf(
            'Generated Composer manifest "%s" is invalid: %s',
            $path,
            implode('; ', $errors),
        ));
    }
}
