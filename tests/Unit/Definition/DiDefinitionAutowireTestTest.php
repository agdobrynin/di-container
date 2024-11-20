<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\FreeInterface;
use Tests\Unit\Definition\Fixtures\Generated\Service0;
use Tests\Unit\Definition\Fixtures\Generated\Service6;
use Tests\Unit\Definition\Fixtures\PrivateConstructor;
use Tests\Unit\Definition\Fixtures\WithoutConstructor;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 *
 * @internal
 */
class DiDefinitionAutowireTestTest extends TestCase
{
    public function testDefinitionInNotClass(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Class "aaa" does not exist');

        (new DiDefinitionAutowire('aaa'))->invoke();
    }

    public function testDefinitionWithPrivateConstructor(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        (new DiDefinitionAutowire(PrivateConstructor::class))->invoke();
    }

    public function testDefinitionWithoutConstructor(): void
    {
        $container = new DiContainer();

        $definition = new DiDefinitionAutowire(WithoutConstructor::class);
        $class = $definition->setContainer($container)->invoke();

        $this->assertInstanceOf(WithoutConstructor::class, $class);
        $this->assertInstanceOf(\ReflectionClass::class, $definition->getDefinition());
        $this->assertEquals('Tests\Unit\Definition\Fixtures\WithoutConstructor', $definition->getDefinition()->getName());
    }

    public function testDefinitionIsInterface(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        (new DiDefinitionAutowire(FreeInterface::class))->invoke();
    }

    public function testDefinitionAutowireNonExistClassGetIdentifier(): void
    {
        $definition = new DiDefinitionAutowire('non-exist-class');

        $this->assertEquals('non-exist-class', $definition->getIdentifier());
    }

    public function testDefinitionAutowireGetDefinitionOnNonExistClass(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Class "non-exist-class" does not exist');

        (new DiDefinitionAutowire('non-exist-class'))->getDefinition();
    }

    public function testDefinitionAutowireWithMethodAddArgumentAndArgumentByReference(): void
    {
        $definition = static function (): \Generator {
            yield 'serviceZero' => diAutowire(Service0::class);

            // ... may may definitions.

            yield (new DiDefinitionAutowire(Service6::class))
                ->addArgument('service', diReference('serviceZero'))
            ;
        };

        $container = (new DiContainerFactory())->make($definition());

        $class6 = $container->get(Service6::class);

        $this->assertInstanceOf(Service0::class, $class6->service);
    }
}
