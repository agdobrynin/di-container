<?php

declare(strict_types=1);

namespace Tests\LazyDefinitionIterator;

use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(LazyDefinitionIterator::class)]
class ImmutableTest extends TestCase
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

    public function testIsArrayAccessByOffsetUnset(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('LazyDefinitionIterator is immutable');

        (new LazyDefinitionIterator($this->container, []))->offsetUnset('aaaa');
    }

    public function testIsArrayAccessByOffsetSet(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('LazyDefinitionIterator is immutable');

        (new LazyDefinitionIterator($this->container, []))->offsetSet('aaaa', 'vvvv');
    }

    public function testIsArrayAccessByMagicSet(): void
    {
        $ld = new LazyDefinitionIterator($this->container, []);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('LazyDefinitionIterator is immutable');

        $ld['aaaa'] = 'vvvv';
    }

    public function testIsArrayAccessByMagicUnset(): void
    {
        $ld = new LazyDefinitionIterator($this->container, []);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('LazyDefinitionIterator is immutable');

        unset($ld['aaaa']);
    }
}
