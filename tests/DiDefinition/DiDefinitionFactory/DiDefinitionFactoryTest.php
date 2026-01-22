<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\SetupAttributeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bat;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Baz;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Quux;

use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(SetupConfigureMethod::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SetupAttributeTrait::class)]
class DiDefinitionFactoryTest extends TestCase
{
    public function testGetDefinitionSuccess(): void
    {
        $factory = new DiDefinitionFactory(Foo::class);

        self::assertEquals(Foo::class, $factory->getDefinition());
        self::assertEquals('__invoke', $factory->getFactoryMethod());
        // get again with DiDefinitionFactory::$verifiedDefinition
        self::assertEquals(Foo::class, $factory->getDefinition());

        $mockContainer = $this->createMock(DiContainerInterface::class);

        $argBuilder = $factory->exposeFactoryMethodArgumentBuilder($mockContainer);
        self::assertInstanceOf(ArgumentBuilderInterface::class, $argBuilder);
        self::assertEquals('__invoke', $argBuilder->getFunctionOrMethod()->getName());
        // test cached get argumentBuilder
        self::assertInstanceOf(ArgumentBuilderInterface::class, $factory->exposeFactoryMethodArgumentBuilder($mockContainer));
    }

    #[DataProvider('dataProviderFail')]
    public function testGetDefinitionFail(string $class): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        (new DiDefinitionFactory($class))->getDefinition();
    }

    #[DataProvider('dataProviderFail')]
    public function testGetFactoryMethodFail(string $class): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        (new DiDefinitionFactory($class))->getFactoryMethod();
    }

    public static function dataProviderFail(): Generator
    {
        yield 'class not implemented Kaspi\DiContainer\Interfaces\DiFactoryInterface' => [Bar::class];

        yield 'random string' => ['Bar'];
    }

    public function testBindArguments(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')->with(Bar::class)->willReturn(new Bar());

        $factory = new DiDefinitionFactory(Foo::class);
        $factory->bindArguments('secure_string', diGet(Bar::class));

        self::assertEquals(
            'ok secure_string Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar',
            $factory->resolve($container)
        );
    }

    public function testSetupImmutableByPhpDefinitionWithoutPhpAttributes(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')->with(Bar::class)->willReturn(new Bar());
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: false
            ),
        );

        $factory = new DiDefinitionFactory(Baz::class);
        $factory->setupImmutable('withBar', diGet(Bar::class));

        self::assertEquals(
            'ok Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar',
            $factory->resolve($container)
        );
    }

    public function testSetupByPhpDefinitionWithoutPhpAttributes(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')->with(Bar::class)->willReturn(new Bar());
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: false
            ),
        );

        $factory = new DiDefinitionFactory(Baz::class);
        $factory->setup('setBar', diGet(Bar::class));

        self::assertEquals(
            'ok Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar',
            $factory->resolve($container)
        );
    }

    public function testSetupImmutableByPhpAttributes(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')->with(Bar::class)->willReturn(new Bar());
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: true
            ),
        );

        $factory = new DiDefinitionFactory(Quux::class);

        self::assertEquals(
            'ok Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar',
            $factory->resolve($container)
        );
    }

    public function testSetupByPhpAttributes(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')->with(Bar::class)->willReturn(new Bar());
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: true
            ),
        );

        $factory = new DiDefinitionFactory(Bat::class);

        self::assertEquals(
            'ok Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar',
            $factory->resolve($container)
        );
    }

    public function testExceptionWhenResolve(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve factory class ".+Foo"/');

        $factory = new DiDefinitionFactory(Foo::class);
        $factory->resolve($this->createMock(DiContainerInterface::class));
    }
}
