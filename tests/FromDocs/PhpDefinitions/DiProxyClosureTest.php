<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Closure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithHeavyDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\HeavyDependency;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diProxyClosure')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
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
