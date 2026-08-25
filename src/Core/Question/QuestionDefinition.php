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

namespace Jascha030\Scaffolder\Core\Question;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;

use function preg_match;
use function trim;

abstract class QuestionDefinition
{
    private const KEY_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_.-]*$/';

    public function __construct(
        public readonly string $key,
        public readonly string $prompt,
        public readonly ?string $default = null,
        public readonly bool $required = false,
    ) {
        if (! self::isValidKey($key)) {
            throw InvalidManifestException::invalidField('question', 'key');
        }

        if ('' === trim($prompt)) {
            throw InvalidManifestException::invalidField('question', 'prompt');
        }
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function hasDefault(): bool
    {
        return null !== $this->default;
    }

    public static function isValidKey(string $key): bool
    {
        return 1 === preg_match(self::KEY_PATTERN, $key);
    }
}
