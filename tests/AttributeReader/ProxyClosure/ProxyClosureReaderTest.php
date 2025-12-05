<?php

declare(strict_types=1);

namespace Tests\AttributeReader\ProxyClosure;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\ProxyClosure;
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
#[CoversClass(ProxyClosure::class)]
#[CoversClass(AttributeReader::class)]
class ProxyClosureReaderTest extends TestCase
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

    public function testNoneAsClosure(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse(AttributeReader::getAttributeOnParameter($p, $this->container)->valid());
    }

    public function testManyAsClosureNonVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('ok')]
            #[ProxyClosure('ok2')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can only be applied once per non-variadic Parameter #0.+[ <required> string \$a ]/');

        AttributeReader::getAttributeOnParameter($p, $this->container)->valid();
    }

    public function testInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('ok')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());
        $attrs->rewind();

        $this->assertInstanceOf(ProxyClosure::class, $attrs->current());
        $this->assertEquals('ok', $attrs->current()->getIdentifier());

        $attrs->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($attrs->valid());
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('one')]
            #[ProxyClosure('two')]
            #[ProxyClosure('three')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $attrs = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($attrs->valid());

        foreach ($attrs as $inject) {
            $this->assertContains($inject->getIdentifier(), ['one', 'two', 'three']);
        }

        $this->assertFalse($attrs->valid()); // All Inject fetched, generator empty.
    }
}
