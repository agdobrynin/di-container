<?php

declare(strict_types=1);

namespace Tests\DiContainer;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
class ResolveSelfContainerTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testResolveWithoutConfig(string $id): void
    {
        $this->assertInstanceOf(DiContainer::class, (new DiContainer())->get($id));
    }

    #[DataProvider('dataProvider')]
    public function testResolveWithConfig(string $id): void
    {
        $this->assertInstanceOf(DiContainer::class, (new DiContainer(config: new DiContainerConfig()))->get($id));
    }

    public static function dataProvider(): Generator
    {
        yield 'ContainerInterface' => [ContainerInterface::class];

        yield 'DiContainerInterface' => [DiContainerInterface::class];

        yield 'DiContainer' => [DiContainer::class];
    }
}
