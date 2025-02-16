<?php

declare(strict_types=1);

namespace Tests\LazyDefinitionIterator;

use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class SimpleTest extends TestCase
{
    public function testIsIterableAndEmpty(): void
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $li = new LazyDefinitionIterator($mockContainer, []);

        $this->assertIsIterable($li);
        $this->assertFalse($li->valid());
        $this->assertNull($li->current());
        $this->assertNull($li->key());
    }
}
