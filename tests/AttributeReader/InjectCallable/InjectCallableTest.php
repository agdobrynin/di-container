<?php

declare(strict_types=1);

namespace Tests\AttributeReader\InjectCallable;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class InjectCallableTest extends TestCase
{
    private ?ContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testInjectByCallableEmpty(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse(AttributeReader::getAttributeOnParameter($p, $this->container)->valid());
    }

    public function testManyInjectByCallableNonVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func')]
            #[InjectByCallable('func2')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can only be applied once per non-variadic Parameter #0.+[ <required> string \$a ].+InjectCallableTest::.+()/');

        AttributeReader::getAttributeOnParameter($p, $this->container)->valid();
    }

    public function testInjectByCallableNonVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('func', $attrs->current()->getIdentifier());

        $attrs->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($attrs->valid());
    }

    public function testInjectByCallableVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func1')]
            #[InjectByCallable('func2')]
            #[InjectByCallable('func3')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('func1', $attrs->current()->getIdentifier());

        $attrs->next();

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('func2', $attrs->current()->getIdentifier());

        $attrs->next();

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('func3', $attrs->current()->getIdentifier());

        $attrs->next();

        $this->assertFalse($attrs->valid());
    }
}
