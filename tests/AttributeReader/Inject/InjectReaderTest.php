<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Inject;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionParameter;
use Tests\AttributeReader\Inject\Fixtures\SuperClass;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(Inject::class)]
#[CoversClass(AttributeReader::class)]
class InjectReaderTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testNoneInject(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse(AttributeReader::getAttributeOnParameter($p, $this->container)->valid());
    }

    public function testManyInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[Inject]
            #[Inject]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic Parameter #0 [ <required> string $a ] in');

        AttributeReader::getAttributeOnParameter($p, $this->container)->valid();
    }

    public function testInjectNonVariadicParameterFail(): void
    {
        $f = static fn (
            #[Inject]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireParameterTypeException::class);
        $this->expectExceptionMessageMatches('/Cannot automatically resolve dependency.+string \$a/');

        AttributeReader::getAttributeOnParameter($p, $this->container)->valid();
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[Inject('one')]
            #[Inject('two')]
            #[Inject('three')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($injects->valid());

        $identifiers = ['one', 'two', 'three']; // Inject id argument for parameter $a in function $f

        foreach ($injects as $k => $inject) {
            $this->assertEquals($identifiers[$k], $injects->current()->getIdentifier());
        }

        $this->assertFalse($injects->valid()); // All Inject fetched, generator empty.
    }

    public function testInjectNonBuiltinParameter(): void
    {
        $f = static fn (
            #[Inject]
            SuperClass $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($injects->valid());
        $this->assertEquals(SuperClass::class, $injects->current()->getIdentifier());
    }

    public function testInjectUnionTypeParameter(): void
    {
        $f = static fn (
            #[Inject]
            string|SuperClass $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->container->expects($this->once())
            ->method('has')->with(SuperClass::class)
            ->willReturn(true)
        ;

        $injects = AttributeReader::getAttributeOnParameter($p, $this->container);

        $this->assertTrue($injects->valid());
        $this->assertEquals(SuperClass::class, $injects->current()->getIdentifier());
    }
}
