<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassWithHeavyDependency;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class DiProxyClosureTest extends TestCase
{
    public function testDiProxyClosure(): void
    {
        // use Attribute
        $container = new DiContainer(
            config: new DiContainerConfig(useAttribute: true),
        );

        // свойство ClassWithHeavyDependency::$heavyDependency
        // ещё не инициализировано.
        $someClass = $container->get(ClassWithHeavyDependency::class);

        $this->assertInstanceOf(ClassWithHeavyDependency::class, $someClass);

        $this->assertEquals(
            \Closure::class,
            (new \ReflectionProperty($someClass, 'heavyDependency'))->getType()->getName()
        );

        $this->assertEquals('doMake in LiteDependency', $someClass->doLiteDependency());

        // Внутри метода инициализируется
        // свойство ClassWithHeavyDependency::$heavyDependency
        // через Closure вызов (callback функция)
        $this->assertEquals('doMake in HeavyDependency', $someClass->doHeavyDependency());
    }
}
