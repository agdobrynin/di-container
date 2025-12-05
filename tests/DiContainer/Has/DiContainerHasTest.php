<?php

declare(strict_types=1);

namespace Tests\DiContainer\Has;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\DiContainer\Has\Fixtures\ClassWithSimpleDependency;
use Tests\DiContainer\Has\Fixtures\ExcludeClass;
use Tests\DiContainer\Has\Fixtures\ExcludeInterface;
use Tests\DiContainer\Has\Fixtures\MyInterface;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(Helper::class)]
class DiContainerHasTest extends TestCase
{
    public function testHasDefinitionWithNull(): void
    {
        $container = new DiContainer(['null' => null]);

        $this->assertTrue($container->has('null'));
    }

    #[DataProvider('dataProvideWithZeroConfig')]
    public function testHasWithZeroConfig(string $id): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
        );
        $container = new DiContainer(config: $config);

        $this->assertTrue($container->has($id));
    }

    public static function dataProvideWithZeroConfig(): Generator
    {
        yield 'class' => [ClassWithSimpleDependency::class];

        yield 'interface' => [MyInterface::class];
    }

    #[DataProvider('dataProvideWithoutZeroConfig')]
    public function testHasWithoutZeroConfig(string $id): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false,
        );
        $container = new DiContainer(config: $config);

        $this->assertFalse($container->has($id));
    }

    public static function dataProvideWithoutZeroConfig(): Generator
    {
        yield 'class' => [ClassWithSimpleDependency::class];

        yield 'interface' => [MyInterface::class];
    }

    public function testHasContainerInterfaceWithZeroConfig(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
        );

        $this->assertTrue((new DiContainer(config: $config))->has(ContainerInterface::class));
    }

    public function testHasContainerInterfaceWithoutZeroConfig(): void
    {
        $this->assertTrue((new DiContainer())->has(ContainerInterface::class));
    }

    public function testAutowireExcludeAttributeOnClassWithZeroConfig(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
        );

        $this->assertFalse((new DiContainer(config: $config))->has(ExcludeClass::class));
    }

    public function testAutowireExcludeAttributeOnInterfaceWithZeroConfig(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
        );

        $this->assertFalse((new DiContainer(config: $config))->has(ExcludeInterface::class));
    }
}
