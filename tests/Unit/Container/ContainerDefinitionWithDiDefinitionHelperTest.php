<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

use function Kaspi\DiContainer\Function\diDefinition;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\diDefinition
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class ContainerDefinitionWithDiDefinitionHelperTest extends TestCase
{
    public function testDefinitionInterfaceAndClassEachDefinition(): void
    {
        $definition1 = diDefinition(containerKey: Interfaces\SumInterface::class, definition: Classes\Sum::class, arguments: ['init' => 50]);
        $definition2 = diDefinition(containerKey: Classes\Sum::class, arguments: ['init' => 10], isSingleton: true);
        $c = (new DiContainerFactory())->make($definition1 + $definition2);

        $this->assertEquals(60, $c->get(Interfaces\SumInterface::class)->add(10));
        $this->assertEquals(20, $c->get(Classes\Sum::class)->add(10));
        $this->assertSame($c->get(Classes\Sum::class), $c->get(Classes\Sum::class));
    }

    public function testDefinitionInterfaceAndClass(): void
    {
        $definition = [
            Interfaces\SumInterface::class => diDefinition(definition: Classes\Sum::class, arguments: ['init' => 50]),
            Classes\Sum::class => diDefinition(arguments: ['init' => 10], isSingleton: true),
        ];
        $c = (new DiContainerFactory())->make($definition);

        $this->assertEquals(60, $c->get(Interfaces\SumInterface::class)->add(10));
        $this->assertEquals(20, $c->get(Classes\Sum::class)->add(10));
        $this->assertSame($c->get(Classes\Sum::class), $c->get(Classes\Sum::class));
    }
}
