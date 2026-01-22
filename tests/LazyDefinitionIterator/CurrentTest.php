<?php

declare(strict_types=1);

namespace Tests\LazyDefinitionIterator;

use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(LazyDefinitionIterator::class)]
class CurrentTest extends TestCase
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

    public function testCurrentSuccess(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('bar')
            ->willReturn('Lorem')
        ;

        $li = new LazyDefinitionIterator($this->container, ['foo' => 'bar']);

        $this->assertEquals('Lorem', $li->current());
    }

    public function testCurrentFail(): void
    {
        $this->container->expects(self::never())
            ->method('get')
        ;

        $li = new LazyDefinitionIterator($this->container, []);

        $this->assertFalse($li->current());
    }
}
