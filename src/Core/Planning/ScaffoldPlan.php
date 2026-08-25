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

use Jascha030\Scaffolder\Core\Operation\FileOperation;

final class ScaffoldPlan
{
    /**
     * @param list<FileOperation> $operations
     */
    public function __construct(
        public readonly string $destination,
        public readonly array $operations,
    ) {
    }
}
