<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\Db;
use Tests\Fixtures\Classes\DbDiFactory;
use Tests\Fixtures\Classes\FileCache;
use Tests\Fixtures\Classes\Interfaces\SumInterface;
use Tests\Fixtures\Classes\Sum;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
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
            FileCache::class => [
                DiContainerInterface::SINGLETON => false,
            ],
        ]);

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionTrueByDefinition(): void
    {
        $c = (new DiContainerFactory())->make([
            FileCache::class => [
                DiContainerInterface::SINGLETON => true,
            ],
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

        $c->set(id: FileCache::class, definition: FileCache::class);

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionBySetMethodFalse(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(id: FileCache::class, definition: FileCache::class, isSingleton: false);

        $this->assertNotSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionBySetMethodTrue(): void
    {
        $c = new DiContainer(
            config: new DiContainerConfig()
        );

        $c->set(id: FileCache::class, definition: FileCache::class, isSingleton: true);

        $this->assertSame($c->get(FileCache::class), $c->get(FileCache::class));
    }

    public function testContainerSharedDefinitionClosureTrue(): void
    {
        $definition = [
            FileCache::class => [
                static fn () => new FileCache(),
                DiContainerInterface::SINGLETON => true,
            ],
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
            FileCache::class => [
                static fn () => new FileCache(),
                DiContainerInterface::SINGLETON => false,
            ],
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
            Db::class => DbDiFactory::class,
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
            Db::class => [
                DbDiFactory::class,
                DiContainerInterface::SINGLETON => false,
            ],
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
            Db::class => [
                DbDiFactory::class,
                DiContainerInterface::SINGLETON => true,
            ],
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
            SumInterface::class => Sum::class, // default service not shared defined in DiContainerConfig!
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
            SumInterface::class => [
                Sum::class,
                DiContainerInterface::SINGLETON => false, // service not shared!
            ],
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
            SumInterface::class => [
                Sum::class,
                DiContainerInterface::SINGLETON => true, // service shared!
            ],
        ];

        $c = new DiContainer(
            definitions: $definition,
            config: new DiContainerConfig()
        );

        $this->assertInstanceOf(Sum::class, $c->get(SumInterface::class));
        $this->assertSame($c->get(SumInterface::class), $c->get(SumInterface::class));
    }
}
