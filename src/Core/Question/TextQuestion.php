<?php

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

        if (null !== $this->pattern) {
            RegularExpression::assertValid($this->pattern);
        }
    }
}
