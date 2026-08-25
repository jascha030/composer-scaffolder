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

final class TextQuestion extends QuestionDefinition
{
    public function __construct(
        string $key,
        string $prompt,
        mixed $default = null,
        bool $required = false,
        public readonly ?string $pattern = null,
    ) {
        parent::__construct($key, $prompt, $default, $required);
    }
}
