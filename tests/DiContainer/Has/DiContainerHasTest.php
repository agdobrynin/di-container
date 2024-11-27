<?php

declare(strict_types=1);

namespace Tests\DiContainer\Has;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\DiContainer\Has\Fixtures\ClassWithSimpleDependency;
use Tests\DiContainer\Has\Fixtures\MyInterface;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 *
 * @internal
 */
class DiContainerHasTest extends TestCase
{
    public function testHasDefinitionWithNull(): void
    {
        $container = new DiContainer(['null' => null]);

        $this->assertTrue($container->has('null'));
    }

    public function dataProvideWithZeroConfig(): \Generator
    {
        yield 'class' => [ClassWithSimpleDependency::class];

        yield 'interface' => [MyInterface::class];
    }

    /**
     * @dataProvider dataProvideWithZeroConfig
     */
    public function testHasWithZeroConfigDefinition(string $id): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
        );
        $container = new DiContainer(config: $config);

        $this->assertTrue($container->has($id));
    }

    public function dataProvideWithoutZeroConfig(): \Generator
    {
        yield 'class' => [ClassWithSimpleDependency::class];

        yield 'interface' => [MyInterface::class];
    }

    /**
     * @dataProvider dataProvideWithoutZeroConfig
     */
    public function testHasWithoutZeroConfigDefinition(string $id): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false,
        );
        $container = new DiContainer(config: $config);

        $this->assertFalse($container->has($id));
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
}
