<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Inject;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\Inject\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getInjectAttribute
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class InjectReaderTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use AttributeReaderTrait;
    use DiContainerTrait; // 🧨 need for abstract method getContainer in AttributeReaderTrait.

    public function testNoneInject(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $this->assertFalse($this->getInjectAttribute($p)->valid());
    }

    public function testManyInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[Inject]
            #[Inject]
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $this->getInjectAttribute($p)->valid();
    }

    public function testInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[Inject]
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $injects = $this->getInjectAttribute($p);

        $this->assertTrue($injects->valid());
        $injects->rewind();

        $this->assertInstanceOf(Inject::class, $injects->current());
        $this->assertEquals('', $injects->current()->getIdentifier());

        $injects->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($injects->valid());
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[Inject('one')]
            #[Inject('two')]
            #[Inject('three')]
            string ...$a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $injects = $this->getInjectAttribute($p);

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
        $p = new \ReflectionParameter($f, 0);

        $injects = $this->getInjectAttribute($p);

        $this->assertTrue($injects->valid());
        $this->assertEquals(SuperClass::class, $injects->current()->getIdentifier());
    }

    public function testInjectUnionTypeParameter(): void
    {
        $f = static fn (
            #[Inject]
            string|SuperClass $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('has')->with(SuperClass::class)
            ->willReturn(true)
        ;
        $this->setContainer($mockContainer);

        $injects = $this->getInjectAttribute($p);

        $this->assertTrue($injects->valid());
        $this->assertEquals(SuperClass::class, $injects->current()->getIdentifier());
    }
}
