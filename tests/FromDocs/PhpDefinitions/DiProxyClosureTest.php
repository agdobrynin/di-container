<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Closure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithHeavyDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\HeavyDependency;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
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
        $definition = [
            diAutowire(ClassWithHeavyDependency::class)
                ->bindArguments(
                    heavyDependency: diProxyClosure(HeavyDependency::class),
                ),
        ];

        // Not use Attribute
        $container = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig(useAttribute: false),
        );

        // свойство ClassWithHeavyDependency::$heavyDependency
        // ещё не инициализировано.
        $someClass = $container->get(ClassWithHeavyDependency::class);

        $this->assertInstanceOf(ClassWithHeavyDependency::class, $someClass);

        $this->assertEquals(
            Closure::class,
            (new ReflectionProperty($someClass, 'heavyDependency'))->getType()->getName()
        );

        $this->assertEquals('doMake in LiteDependency', $someClass->doLiteDependency());

        // Внутри метода инициализируется
        // свойство ClassWithHeavyDependency::$heavyDependency
        // через Closure вызов (callback функция)
        $this->assertEquals('doMake in HeavyDependency', $someClass->doHeavyDependency());
    }

    public function testProxyClosureAsDefinition(): void
    {
        $definition = [
            'service-one' => diProxyClosure(HeavyDependency::class),
        ];

        $container = new DiContainer($definition, new DiContainerConfig(useAttribute: false));

        $this->assertInstanceOf(Closure::class, $container->get('service-one'));
        $this->assertInstanceOf(HeavyDependency::class, $container->get('service-one')());
    }
}
