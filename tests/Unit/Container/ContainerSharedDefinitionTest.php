<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\Db;
use Tests\Fixtures\Classes\DbDiFactory;
use Tests\Fixtures\Classes\FileCache;
use Tests\Fixtures\Classes\Interfaces\SumInterface;
use Tests\Fixtures\Classes\Sum;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class ContainerSharedDefinitionTest extends TestCase
{
    public function testContainerSharedDefinitionDefaultFromDiContainerFactory(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionFalseByDefinition(): void
    {
        $c = (new DiContainerFactory())->make([
            diAutowire(FileCache::class, isSingleton: false),
        ]);

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionTrueByDefinition(): void
    {
        $c = (new DiContainerFactory())->make([
            diAutowire(FileCache::class, isSingleton: true),
        ]);

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionFalseByConfig(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig(isSingletonServiceDefault: false)
        );

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionTrueByConfig(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig(isSingletonServiceDefault: true)
        );

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionBySetMethodDefault(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(id: FileCache::class, definition: diAutowire(FileCache::class));

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionBySetMethodFalse(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(FileCache::class, diAutowire(FileCache::class, isSingleton: false));

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionBySetMethodTrue(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(FileCache::class, diAutowire(FileCache::class, isSingleton: true));

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionIsArrayBySetMethodTrue(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(FileCache::class, diAutowire(FileCache::class, isSingleton: true));

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionClosureTrue(): void
    {
        $definition = [
            FileCache::class => diCallable(static fn () => new FileCache(), isSingleton: true),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionClosureFalse(): void
    {
        $definition = [
            FileCache::class => diCallable(static fn () => new FileCache(), isSingleton: false),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionDiFabricDefault(): void
    {
        $definition = [
            Db::class => diAutowire(DbDiFactory::class),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertEquals(['one', 'two'], $c->get(Db::class)->all());
        $this->assertNotSame($c->get(Db::class), $c->get(Db::class));
    }

    public function testContainerSharedDefinitionDiFabricFalse(): void
    {
        $definition = [
            Db::class => diAutowire(DbDiFactory::class, isSingleton: false),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertEquals(['one', 'two'], $c->get(Db::class)->all());
        $this->assertNotSame($c->get(Db::class), $c->get(Db::class));
    }

    public function testContainerSharedDefinitionDiFabricTrue(): void
    {
        $definition = [
            Db::class => diAutowire(DbDiFactory::class, isSingleton: true),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertEquals(['one', 'two'], $c->get(Db::class)->all());
        $this->assertSame($c->get(Db::class), $c->get(Db::class));
    }

    public function testContainerSharedDefinitionInterfaceDefault(): void
    {
        $definition = [
            SumInterface::class => diAutowire(Sum::class), // default service not shared defined in DiContainerConfig!
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertNotSame($c->get(SumInterface::class), $c->get(SumInterface::class));
    }

    public function testContainerSharedDefinitionInterfaceFalse(): void
    {
        $definition = [
            SumInterface::class => diAutowire(Sum::class, isSingleton: false),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertNotSame($c->get(SumInterface::class), $c->get(SumInterface::class));
    }

    public function testContainerSharedDefinitionInterfaceTrue(): void
    {
        $definition = [
            SumInterface::class => diAutowire(Sum::class, isSingleton: true),
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertSame($c->get(SumInterface::class), $c->get(SumInterface::class));
    }

    public function testContainerSetMethodSharedDefinitionInterfaceTrue(): void
    {
        $c = (new DiContainer(config: new DiContainerConfig()))
            ->set(SumInterface::class, diAutowire(Sum::class, ['init' => 10], true))
        ;

        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertSame($c->get(SumInterface::class), $c->get(SumInterface::class));
        $this->assertEquals(20, $c->get(SumInterface::class)->add(10));
    }
}
