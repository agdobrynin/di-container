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
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
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
        ];

        $container = (new DiContainerFactory())->make($def);
        $this->assertSame($container->get('callback'), $container->get('callback'));
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
