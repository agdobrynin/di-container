<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\ContainerException;
use PHPUnit\Framework\TestCase;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\DependencyClass;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClass;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassDiFactory;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassFailDiFactory;
use Tests\DiContainer\ResolveByDiFactory\Fixtures\MyClassSingleton;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
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
        $container = new DiContainer(config: $config);
        $res = $container->get(MyClassSingleton::class);

        $this->assertSame($res, $container->get(MyClassSingleton::class));
    }

    public function testResolveByDiFactoryViaAttributeFailDiFactory(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true
        );
        $container = new DiContainer(config: $config);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Parameter \'service\.one\' must be implement.+DiFactoryInterface/');

        $container->get(MyClassFailDiFactory::class);
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
}