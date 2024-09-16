<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\ClassWithFiledFactory;
use Tests\Fixtures\Attributes\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\Factory
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
        $this->expectExceptionMessage("must be a 'Kaspi\\DiContainer\\Interfaces\\FactoryInterface' interface");

        (new DiContainerFactory())->make()->get(ClassWithFiledFactory::class);
    }
}
