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
    private object $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testIsIterableAndEmpty(): void
    {
        $li = new LazyDefinitionIterator($this->container, []);

        $this->assertIsIterable($li);
        $this->assertFalse($li->valid());
        $this->assertNull($li->current());
        $this->assertNull($li->key());
    }

    public function testArrayAccessMagicIsset(): void
    {
        $li = new LazyDefinitionIterator($this->container, ['ok' => 'something']);

        $this->assertTrue(isset($li['ok']));
        $this->assertFalse(isset($li['any']));
    }

    public function testByOffsetExists(): void
    {
        $li = new LazyDefinitionIterator($this->container, ['ok' => 'something']);

        $this->assertTrue($li->offsetExists('ok'));
        $this->assertFalse($li->offsetExists('any'));
    }

    public function testByHas(): void
    {
        $li = new LazyDefinitionIterator($this->container, ['ok' => 'something']);

        $this->assertTrue($li->has('ok'));
    }
}
