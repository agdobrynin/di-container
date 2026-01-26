<?php

declare(strict_types=1);

namespace Tests\DiContainer\GetDefinition;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainer\GetDefinition\Fixtures\Bar;
use Tests\DiContainer\GetDefinition\Fixtures\BatInterface;
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
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(Service::class)]
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
    }

    public function testNotBoundInterface(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Cannot create definition via container identifier "'.BazInterface::class.'"');

        $container = new DiContainer(config: new DiContainerConfig(true, false));
        $container->getDefinition(BazInterface::class);
    }

    public function testGetLink(): void
    {
        $container = new DiContainer([
            'service.one' => diGet('service.two'),
            'service.two' => diGet('service.one'),
        ], new DiContainerConfig());

        $def = $container->getDefinition('service.one');

        self::assertInstanceOf(DiDefinitionLinkInterface::class, $def);
        self::assertEquals('service.two', $def->getDefinition());
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

    public function testCircularResolveInterface(): void
    {
        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessage('"foo" -> "bar" -> "foo".');

        $container = new DiContainer(
            [
                'foo' => diGet('bar'),
                'bar' => diGet('foo'),
            ],
            config: new DiContainerConfig(useZeroConfigurationDefinition: true, useAttribute: true)
        );

        $container->get(BatInterface::class);
    }

    public function testResolveInterfaceViaStringIdentifier(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $container = new DiContainer(config: new DiContainerConfig(useZeroConfigurationDefinition: true, useAttribute: true));

        $container->getDefinition(BatInterface::class);
    }
}
