<?php

declare(strict_types=1);

namespace Tests\DiContainer;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ContainerAlreadyRegisteredException::class)]
#[CoversClass(NotFoundException::class)]
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

    #[DataProvider('dataProvider')]
    public function testSetReservedId(string $id): void
    {
        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);

        (new DiContainer())->set($id, new stdClass());
    }

    #[DataProvider('dataProvider')]
    public function testGetDefinition(string $id): array
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new DiContainer([$id => new stdClass()]);
        $container->getDefinition($id);
    }

    #[DataProvider('dataProvider')]
    public function testDefinitions($id): void
    {
        $container = new DiContainer([$id => new stdClass()]);
        $definitions = [...$container->getDefinitions()];

        self::assertArrayNotHasKey($id, $definitions);
    }

    public static function dataProvider(): Generator
    {
        yield 'ContainerInterface' => [ContainerInterface::class];

        yield 'DiContainerInterface' => [DiContainerInterface::class];

        yield 'DiContainer' => [DiContainer::class];
    }
}
