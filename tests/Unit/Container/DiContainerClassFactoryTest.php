<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\ClassWithFactoryArgument;
use Tests\Fixtures\Attributes\ClassWithFiledFactory;
use Tests\Fixtures\Attributes\ClassWithFiledFactoryOnProperty;
use Tests\Fixtures\Attributes\SuperClass;
use Tests\Fixtures\Classes\Interfaces\SumInterface;
use Tests\Fixtures\Classes\Sum;
use Tests\Fixtures\Classes\SumDiFactoryForInterface;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerFactory
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("must be implement 'Kaspi\\DiContainer\\Interfaces\\DiFactoryInterface'");

        (new DiContainerFactory())->make()->get(ClassWithFiledFactory::class);
    }

    public function testCallMethodWithArgumentWithFactory(): void
    {
        $c = (new DiContainerFactory())->make();
        $res = (new Autowired())->callMethod($c, SuperClass::class, 'getArray');

        $this->assertEquals(['Hello', 'World'], $res);
    }

    public function testCallMethodWithArgumentWithWrongFactory(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("must be implement 'Kaspi\\DiContainer\\Interfaces\\DiFactoryInterface'");

        (new Autowired())->callMethod($c, ClassWithFiledFactoryOnProperty::class, 'make');
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
            SumInterface::class => SumDiFactoryForInterface::class,
        ]);

        // See in class SumDiFactoryForInterface where init value 10.
        $this->assertInstanceOf(SumInterface::class, $c->get(SumInterface::class));
        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertEquals(20, $c->get(SumInterface::class)->add(10));
    }
}
