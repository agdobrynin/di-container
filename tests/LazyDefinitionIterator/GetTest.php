<?php

declare(strict_types=1);

namespace Tests\LazyDefinitionIterator;

use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 */
#[CoversClass(LazyDefinitionIterator::class)]
#[CoversClass(CallCircularDependencyException::class)]
#[CoversClass(NotFoundException::class)]
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
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('No entry was found for "foo" identifier.');

        $this->container->expects(self::never())
            ->method('get')
        ;

        $li = new LazyDefinitionIterator($this->container, []);

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
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('No entry was found for "foo" identifier.');

        $this->container->expects(self::never())
            ->method('get')
        ;

        (new LazyDefinitionIterator($this->container, []))['foo'];
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
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('No entry was found for "foo" identifier.');

        $this->container->expects(self::never())
            ->method('get')
        ;

        (new LazyDefinitionIterator($this->container, []))->offsetGet('foo');
    }
}
