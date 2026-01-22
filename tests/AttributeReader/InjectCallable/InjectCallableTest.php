<?php

declare(strict_types=1);

namespace Tests\AttributeReader\InjectCallable;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(InjectByCallable::class)]
#[CoversClass(AttributeReader::class)]
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
        $this->expectExceptionMessageMatches('/can be applied once per non-variadic Parameter #0.+[ <required> string \$a ].+InjectCallableTest::.+()/');

        AttributeReader::getAttributeOnParameter($p, $this->container)->valid();
    }

    public function testInjectByCallableNonVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('\uniqid')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('\uniqid', $attrs->current()->getCallable());

        $attrs->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($attrs->valid());
    }

    public function testInjectByCallableVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('\uniqid')]
            #[InjectByCallable('\microtime')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('\uniqid', $attrs->current()->getCallable());

        $attrs->next();

        $this->assertInstanceOf(InjectByCallable::class, $attrs->current());
        $this->assertEquals('\microtime', $attrs->current()->getCallable());

        $attrs->next();

        $this->assertFalse($attrs->valid());
    }
}
