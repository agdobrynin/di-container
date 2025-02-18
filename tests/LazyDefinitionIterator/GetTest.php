<?php

declare(strict_types=1);

namespace Tests\LazyDefinitionIterator;

use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class GetTest extends TestCase
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

    public function testGetSuccess(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('bar')
            ->willReturn('Lorem')
        ;

        $li = new LazyDefinitionIterator($this->container, ['foo' => 'bar']);

        $this->assertEquals('Lorem', $li->get('foo'));
    }

    public function testGetFail(): void
    {
        $this->container->expects(self::never())
            ->method('get')
        ;

        $li = new LazyDefinitionIterator($this->container, []);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Definition "foo" not found');

        $li->get('foo');
    }

    public function testArrayAccessSuccess(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('bar')
            ->willReturn('Lorem')
        ;

        $li = new LazyDefinitionIterator($this->container, ['foo' => 'bar']);

        $this->assertEquals('Lorem', $li['foo']);
    }

    public function testArrayAccessFail(): void
    {
        $this->container->expects(self::never())
            ->method('get')
        ;

        $li = new LazyDefinitionIterator($this->container, []);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Definition "foo" not found');

        $li['foo'];
    }

    public function testByOffsetGetSuccess(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('bar')
            ->willReturn('Lorem')
        ;

        $li = new LazyDefinitionIterator($this->container, ['foo' => 'bar']);

        $this->assertEquals('Lorem', $li->offsetGet('foo'));
    }

    public function testByOffsetGetFail(): void
    {
        $this->container->expects(self::never())
            ->method('get')
        ;

        $li = new LazyDefinitionIterator($this->container, []);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Definition "foo" not found');

        $li->offsetGet('foo');
    }
}
