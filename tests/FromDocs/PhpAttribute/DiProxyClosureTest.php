<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Closure;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassWithHeavyDependency;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diProxyClosure')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(ProxyClosure::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
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
            Closure::class,
            (new ReflectionProperty($someClass, 'heavyDependency'))->getType()->getName()
        );

        $this->assertEquals('doMake in LiteDependency', $someClass->doLiteDependency());

        // Внутри метода инициализируется
        // свойство ClassWithHeavyDependency::$heavyDependency
        // через Closure вызов (callback функция)
        $this->assertEquals('doMake in HeavyDependency', $someClass->doHeavyDependency());
    }
}
