<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Attributes\ClassWithFactoryArgument;
use Tests\Fixtures\Attributes\ClassWithFiledFactory;
use Tests\Fixtures\Attributes\DiFactoryOnPropertyFail;
use Tests\Fixtures\Attributes\DiFactoryOnPropertyFailWithDefaultValue;
use Tests\Fixtures\Attributes\FlyClass;
use Tests\Fixtures\Attributes\FlyWIthFlay;
use Tests\Fixtures\Attributes\SuperClass;
use Tests\Fixtures\Classes\Interfaces\SumInterface;
use Tests\Fixtures\Classes\Sum;
use Tests\Fixtures\Classes\SumDiFactoryForInterface;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 *
 * @internal
 */
class DiContainerClassFactoryTest extends TestCase
{
    public function testFactoryByClass(): void
    {
        $class = (new DiContainerFactory())->make()->get(SuperClass::class);

        $this->assertEquals('Piter', $class->name);
        $this->assertEquals(22, $class->age);
    }

    public function testFactoryByClassWithInvalidClass(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage("must be implement 'Kaspi\\DiContainer\\Interfaces\\DiFactoryInterface'");

        (new DiContainerFactory())->make()->get(ClassWithFiledFactory::class);
    }

    public function testFactoryForConstructorProperty(): void
    {
        $c = (new DiContainerFactory())->make(
            ['names' => ['Ivan', 'Piter', 'Vasiliy']]
        );

        $this->assertEquals(
            ['Ivan', 'Piter', 'Vasiliy'],
            $c->get(ClassWithFactoryArgument::class)->arrayObject->getArrayCopy()
        );
    }

    public function testInterfaceByFactory(): void
    {
        $c = (new DiContainerFactory())->make([
            SumInterface::class => diAutowire(SumDiFactoryForInterface::class),
        ]);

        // See in class SumDiFactoryForInterface where init value 10.
        $this->assertInstanceOf(SumInterface::class, $c->get(SumInterface::class));
        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertEquals(20, $c->get(SumInterface::class)->add(10));
    }

    public function testTwoParamsWithOneType(): void
    {
        $c = (new DiContainerFactory())->make();
        $class = $c->get(FlyWIthFlay::class);

        $this->assertNotSame($class->fly1, $class->fly2);
        $this->assertInstanceOf(FlyClass::class, $class->fly1);
        $this->assertInstanceOf(FlyClass::class, $class->fly2);
    }

    public function testDiFactoryForProperty(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage("must be implement 'Kaspi\\DiContainer\\Interfaces\\DiFactoryInterface' interface");

        (new DiContainerFactory())->make()->get(DiFactoryOnPropertyFail::class);
    }

    public function testDiFactoryForPropertyWithDefaultValue(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage("must be implement 'Kaspi\\DiContainer\\Interfaces\\DiFactoryInterface' interface");

        (new DiContainerFactory())->make()->get(DiFactoryOnPropertyFailWithDefaultValue::class);
    }
}
