<?php

declare(strict_types=1);

namespace Tests\Compiler\IdsIterator;

use Kaspi\DiContainer\Compiler\IdsIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IdsIterator::class)]
class IdsIteratorTest extends TestCase
{
    public function testIdsIteratorReset(): void
    {
        $iter = new IdsIterator();
        $iter->add('foo');
        $iter->rewind();

        self::assertTrue($iter->valid());

        $iter->reset();

        self::assertFalse($iter->valid());
    }

    public function testIdsIteratorNavigate(): void
    {
        $iter = new IdsIterator();
        $iter->add('foo');
        $iter->add('bar');
        $iter->rewind();

        self::assertEquals('foo', $iter->current());
        $iter->next();
        self::assertEquals('bar', $iter->current());
        $iter->next();
        self::assertFalse($iter->valid());
    }
}
