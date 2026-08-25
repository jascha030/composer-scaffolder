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

namespace Jascha030\Scaffolder\Tests\Unit\Core;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AnswerBag::class)]
final class AnswerBagTest extends TestCase
{
    #[Test]
    public function itStoresAndRetrievesAnswers(): void
    {
        $bag = new AnswerBag(['key' => 'value']);

        self::assertTrue($bag->has('key'));
        self::assertSame('value', $bag->get('key'));
        self::assertFalse($bag->has('missing'));
        self::assertNull($bag->get('missing'));
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $original = new AnswerBag([]);
        $updated  = $original->with('key', 'value');

        self::assertFalse($original->has('key'));
        self::assertTrue($updated->has('key'));
    }

    #[Test]
    public function itIgnoresDuplicateKeys(): void
    {
        $bag = (new AnswerBag(['key' => 'first']))->with('key', 'second');

        self::assertSame('first', $bag->get('key'));
    }

    #[Test]
    public function itReturnsAllAnswers(): void
    {
        $bag = new AnswerBag(['a' => 'one', 'b' => 'two']);

        self::assertSame(['a' => 'one', 'b' => 'two'], $bag->all());
    }
}
