<?php

declare(strict_types=1);

namespace Tests\DiContainer;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 *
 * @internal
 */
class ResolveSelfContainerTest extends TestCase
{
    public function dataProvider(): Generator
    {
        yield 'ContainerInterface' => [ContainerInterface::class];

        yield 'DiContainerInterface' => [DiContainerInterface::class];

        yield 'DiContainer' => [DiContainer::class];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testResolveWithoutConfig(string $id): void
    {
        $this->assertInstanceOf(DiContainer::class, (new DiContainer())->get($id));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testResolveWithConfig(string $id): void
    {
        $this->assertInstanceOf(DiContainer::class, (new DiContainer(config: new DiContainerConfig()))->get($id));
    }
}
