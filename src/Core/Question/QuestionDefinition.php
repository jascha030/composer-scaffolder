<?php

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
        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw InvalidManifestException::invalidField('question', 'key');
        }

        if ('' === trim($prompt)) {
            throw InvalidManifestException::invalidField('question', 'prompt');
        }
    }
}
