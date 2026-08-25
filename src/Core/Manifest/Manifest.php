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

namespace Jascha030\Scaffolder\Core\Manifest;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;
use Jascha030\Scaffolder\Core\Operation\FileOperation;
use Jascha030\Scaffolder\Core\Question\QuestionDefinition;

final class Manifest
{
    public const SUPPORTED_SCHEMA = 1;

    /**
     * @param list<QuestionDefinition> $questions
     * @param list<FileOperation>      $files
     */
    public function __construct(
        public readonly int $schema,
        public readonly array $questions,
        public readonly array $files,
    ) {
        if (! self::supportsSchema($this->schema)) {
            throw InvalidManifestException::unsupportedSchema($this->schema, self::SUPPORTED_SCHEMA);
        }
    }

    public static function supportsSchema(int $schema): bool
    {
        return self::SUPPORTED_SCHEMA === $schema;
    }
}
