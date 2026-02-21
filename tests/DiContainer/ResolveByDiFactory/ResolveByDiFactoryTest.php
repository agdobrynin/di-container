<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\DependencyClass;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClass;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassDiFactory;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassFailDiFactory;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassMaker;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassSingleton;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\ParameterByDiFactory;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diFactory;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diFactory')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class ResolveByDiFactoryTest extends TestCase
{
    public function testResolveByDiFactoryViaAttributeNoneSingleton(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true
        );
        $container = new DiContainer(config: $config);
        $res = $container->get(MyClass::class);

        $this->assertInstanceOf(DependencyClass::class, $res->dependency);
        $this->assertNull($res->dependency->dependency);
        $this->assertNotSame($res, $container->get(MyClass::class));
    }

    public function testResolveByDiFactoryViaAttributeSingleton(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true
        );
        $container = new DiContainer([
            'security.key' => 'foo_bar_baz',
        ], config: $config);
        $res = $container->get(MyClassSingleton::class);

        $this->assertSame($res, $container->get(MyClassSingleton::class));
    }

    public function testResolveByDiFactoryWithoutAttribute(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: false
        );
        $def = [
            MyClassFailDiFactory::class => diAutowire(MyClassDiFactory::class),
        ];
        $container = new DiContainer($def, config: $config);

        $res = $container->get(MyClass::class);

        $this->assertInstanceOf(DependencyClass::class, $res->dependency);
        $this->assertNull($res->dependency->dependency);
        $this->assertNotSame($res, $container->get(MyClass::class));
    }

    public function testResolveParameterByBindArgumentWithDiFactory(): void
    {
        $container = new DiContainer(
            [
                diAutowire(ParameterByDiFactory::class)
                    ->bindArguments(
                        dependency: diFactory(MyClassMaker::class)
                    ),
            ],
            new DiContainerConfig(useAttribute: false),
        );

        $result = $container->get(ParameterByDiFactory::class);

        self::assertEquals('secure_string', $result->dependency->dependency->dependency);
    }
}
