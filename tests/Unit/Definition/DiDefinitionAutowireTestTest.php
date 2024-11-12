<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\FreeInterface;
use Tests\Unit\Definition\Fixtures\PrivateConstructor;
use Tests\Unit\Definition\Fixtures\WithoutConstructor;

/**
 * @covers \Kaspi\DiContainer\DiContainer::__construct
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class DiDefinitionAutowireTestTest extends TestCase
{
    public function testDefinitionInNotClass(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Class "aaa" does not exist');

        new DiDefinitionAutowire('aaa', true);
    }

    public function testDefinitionWithPrivateConstructor(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        new DiDefinitionAutowire(PrivateConstructor::class, true);
    }

    public function testDefinitionWithoutConstructor(): void
    {
        $container = new DiContainer();

        $definition = new DiDefinitionAutowire(WithoutConstructor::class, true);
        $class = $definition->invoke($container, false);

        $this->assertInstanceOf(WithoutConstructor::class, $class);
        $this->assertEquals('Tests\Unit\Definition\Fixtures\WithoutConstructor', $definition->getDefinition());
    }

    public function testDefinitionIsInterface(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        new DiDefinitionAutowire(FreeInterface::class, true);
    }
}
