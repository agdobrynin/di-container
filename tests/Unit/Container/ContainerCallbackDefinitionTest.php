<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionClosure
 *
 * @internal
 */
class ContainerCallbackDefinitionTest extends TestCase
{
    public function testCallbackDefinitionIsSingletonDefault(): void
    {
        $def = [
            'callback' => static fn () => new \ArrayIterator(['i1']),
        ];

        $container = (new DiContainerFactory())->make($def);

        $this->assertNotSame($container->get('callback'), $container->get('callback'));
    }

    public function testCallbackDefinitionIsSingletonDefaultFromConfig(): void
    {
        $def = [
            'callback' => static fn () => new \ArrayIterator(['i1']),
        ];

        $container = new DiContainer(definitions: $def, config: new DiContainerConfig(isSingletonServiceDefault: true));

        $this->assertSame($container->get('callback'), $container->get('callback'));
    }

    public function testCallbackDefinitionSingletonDefaultButDefinitionSingletonTrue(): void
    {
        $def = [
            'callback' => [
                static fn () => new \ArrayIterator(['i1']),
                DiContainerInterface::SINGLETON => true,
            ],
            'callback2' => [
                static fn () => new \ArrayIterator(['i2']),
            ],
            'callback3' => fn () => new \ArrayIterator(['i3']),
        ];

        $container = (new DiContainerFactory())->make($def);
        $this->assertSame($container->get('callback'), $container->get('callback'));
        $this->assertNotSame($container->get('callback2'), $container->get('callback2'));
        $this->assertNotSame($container->get('callback3'), $container->get('callback3'));
    }

    public function testCallbackDefinitionSingletonDefaultTrueButDefinitionSingletonFalse(): void
    {
        $def = [
            'callback' => [
                static fn () => new \ArrayIterator(['i1']),
                DiContainerInterface::SINGLETON => false,
            ],
        ];

        $container = new DiContainer(definitions: $def, config: new DiContainerConfig(isSingletonServiceDefault: true));
        $this->assertNotSame($container->get('callback'), $container->get('callback'));
    }
}
