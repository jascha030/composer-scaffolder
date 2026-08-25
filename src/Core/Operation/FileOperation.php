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

namespace Jascha030\Scaffolder\Core\Operation;

use Jascha030\Scaffolder\Core\Exception\InvalidManifestException;

use function trim;

final class FileOperation
{
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly OperationMode $mode,
    ) {
        if ('' === trim($this->source)) {
            throw InvalidManifestException::invalidField('file operation', 'source');
        }

        if ('' === trim($this->target)) {
            throw InvalidManifestException::invalidField('file operation', 'target');
        }
    }
}
