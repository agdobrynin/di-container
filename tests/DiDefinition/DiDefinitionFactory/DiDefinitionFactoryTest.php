<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory;

use Generator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bar;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Bat;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Baz;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\Quux;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Enum\SetupConfigureMethod
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
 * @covers \Kaspi\DiContainer\Traits\SetupAttributeTrait
 *
 * @internal
 */
class DiDefinitionFactoryTest extends TestCase
{
    public function testGetDefinitionSuccess(): void
    {
        $factory = new DiDefinitionFactory(Foo::class);

        self::assertEquals(Foo::class, $factory->getDefinition());
        // get again with DiDefinitionFactory::$verifiedDefinition
        self::assertEquals(Foo::class, $factory->getDefinition());
    }

    /**
     * @dataProvider dataProviderFail
     */
    public function testGetDefinitionFail(string $class): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $factory = new DiDefinitionFactory($class);

        $factory->getDefinition();
    }

    public function dataProviderFail(): Generator
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
