<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithHeavyDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\HeavyDependency;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
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
        $definition = [
            diAutowire(ClassWithHeavyDependency::class)
                ->bindArguments(
                    heavyDependency: diProxyClosure(HeavyDependency::class),
                ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        // свойство ClassWithHeavyDependency::$heavyDependency
        // ещё не инициализировано.
        $someClass = $container->get(ClassWithHeavyDependency::class);

        $this->assertInstanceOf(ClassWithHeavyDependency::class, $someClass);

        $this->assertEquals('doMake in LiteDependency', $someClass->doLiteDependency());

        // Внутри метода инициализируется
        // свойство ClassWithHeavyDependency::$heavyDependency
        // через Closure вызов (callback функция)
        $this->assertEquals('doMake in HeavyDependency', $someClass->doHeavyDependency());
    }
}
