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
use Jascha030\Scaffolder\Core\Validation\RegularExpression;

final class TextQuestion extends QuestionDefinition
{
    public function __construct(
        string $key,
        string $prompt,
        ?string $default = null,
        bool $required = false,
        public readonly ?string $pattern = null,
    ) {
        parent::__construct($key, $prompt, $default, $required);

        if ('' === $this->pattern) {
            throw InvalidManifestException::invalidField('question validation', 'pattern');
        }

        if (null !== $this->pattern && ! RegularExpression::isValidPattern($this->pattern)) {
            throw InvalidManifestException::invalidPattern($this->pattern);
        }
    }

    public function hasPattern(): bool
    {
        return null !== $this->pattern;
    }

    public function isMissing(?string $value): bool
    {
        return (null === $value || '' === $value) && $this->required;
    }

    public function patternMatches(string $value): bool
    {
        return null === $this->pattern
            || 1 === preg_match(RegularExpression::compile($this->pattern), $value);
    }

    public function accepts(?string $value): bool
    {
        if ($this->isMissing($value)) {
            return false;
        }

        if (null === $value || '' === $value) {
            return true;
        }

        return $this->patternMatches($value);
    }
}
