<?php

declare(strict_types=1);

namespace Tests\DiContainer\GetDefinition;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainer\GetDefinition\Fixtures\Bar;
use Tests\DiContainer\GetDefinition\Fixtures\BazInterface;
use Tests\DiContainer\GetDefinition\Fixtures\Foo;
use Tests\DiContainer\GetDefinition\Fixtures\Qux;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(CallCircularDependencyException::class)]
#[CoversFunction('Kaspi\DiContainer\diGet')]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
class GetDefinitionTest extends TestCase
{
    #[DataProvider('dataProviderConfig')]
    public function testDefinitionNotExist($id, $definitions, $config): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new DiContainer($definitions, $config);
        $container->getDefinition($id);
    }

    public static function dataProviderConfig(): Generator
    {
        yield 'Null config' => [
            Foo::class,
            [],
            new DiContainerNullConfig(),
        ];

        yield 'zero config false' => [
            Foo::class,
            [
                diAutowire(Bar::class),
            ],
            new DiContainerConfig(useZeroConfigurationDefinition: false),
        ];

        yield 'interface not bind to any entry. config use zero config like true' => [
            BazInterface::class,
            [],
            new DiContainerConfig(true, false),
        ];
    }

    public function testCircular(): void
    {
        $this->expectException(CallCircularDependencyException::class);

        $container = new DiContainer([
            'service.one' => diGet('service.two'),
            'service.two' => diGet('service.one'),
        ], new DiContainerConfig());

        $container->getDefinition('service.one');
    }

    public function testAutowireAttributeException(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $container = new DiContainer(config: new DiContainerConfig());
        $container->getDefinition(Qux::class);
    }

    public function testGetRegisteredDefinition(): void
    {
        $container = new DiContainer(
            [
                diAutowire(Bar::class),
            ],
            new DiContainerNullConfig()
        );

        self::assertInstanceOf(DiDefinitionAutowire::class, $container->getDefinition(Bar::class));
    }

    public function testGetAutoRegisteredDefinition(): void
    {
        $container = new DiContainer(config: new DiContainerConfig(useZeroConfigurationDefinition: true));

        self::assertInstanceOf(DiDefinitionAutowire::class, $container->getDefinition(Bar::class));
    }
}
