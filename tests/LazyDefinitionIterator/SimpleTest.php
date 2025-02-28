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
    private ?object $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testIsIterableAndEmpty(): void
    {
        $li = new LazyDefinitionIterator($this->container, []);

        $this->assertIsIterable($li);
        $this->assertFalse($li->valid());
        $this->assertFalse($li->current());
        $this->assertNull($li->key());
        $this->assertEquals(0, $li->count());
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

    public function testByRewind(): void
    {
        $li = new LazyDefinitionIterator($this->container, ['foo' => 'bar', 'baz' => 'qux']);

        $this->assertEquals(2, $li->count());
        $this->assertTrue($li->valid());
        $this->assertEquals('foo', $li->key());
        $li->next();
        $this->assertEquals('baz', $li->key());
        $li->next();
        $this->assertFalse($li->valid());
        $li->rewind();
        $this->assertTrue($li->valid());
        $this->assertEquals('foo', $li->key());
    }
}
