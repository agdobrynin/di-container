<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig;

use Generator;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\FactoryConfig\Fixtures\ApiClientFactory;
use Tests\Integration\FactoryConfig\Fixtures\ApiClientInterface;
use Tests\Integration\FactoryConfig\Fixtures\Bar;
use Tests\Integration\FactoryConfig\Fixtures\Baz;
use Tests\Integration\FactoryConfig\Fixtures\BazAttr;
use Tests\Integration\FactoryConfig\Fixtures\FactoryClass;
use Tests\Integration\FactoryConfig\Fixtures\FactoryClassArgs;
use Tests\Integration\FactoryConfig\Fixtures\FactoryInvokableClass;
use Tests\Integration\FactoryConfig\Fixtures\FactoryNoneStaticClass;
use Tests\Integration\FactoryConfig\Fixtures\Foo;
use Tests\Integration\FactoryConfig\Fixtures\FooAttrArgs;
use Tests\Integration\FactoryConfig\Fixtures\FooAttrInvokable;
use Tests\Integration\FactoryConfig\Fixtures\FooAttrOne;
use Tests\Integration\FactoryConfig\Fixtures\FooAttrThree;
use Tests\Integration\FactoryConfig\Fixtures\FooAttrTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diFactory;

/**
 * @internal
 */
#[CoversNothing]
class FactoryStaticTest extends TestCase
{
    public function testStaticFactory(): void
    {
        $config = static function () {
            yield Foo::class => diFactory([FactoryClass::class, 'create']);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($config())
            ->build()
        ;

        self::assertEquals('Lorem ipsum one', $container->get(Foo::class)->str);
        self::assertEquals('Lorem ipsum one', $container->get(FooAttrOne::class)->str);
    }

    public function testNoneStaticFactory(): void
    {
        $config = static function () {
            yield diAutowire(Bar::class)
                ->bindArguments('Lorem ipsum non-static method')
            ;

            yield Foo::class => diFactory([FactoryNoneStaticClass::class, 'create']);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($config())
            ->build()
        ;

        self::assertEquals('Lorem ipsum non-static method', $container->get(Foo::class)->str);
        self::assertEquals('Lorem ipsum non-static method', $container->get(FooAttrTwo::class)->str);
    }

    public function testFactoryClassAsContainerId(): void
    {
        $config = static function () {
            yield diAutowire(Bar::class)
                ->bindArguments('Lorem ipsum non-static method')
            ;

            yield 'factories.factory_none_static_class' => diAutowire(FactoryNoneStaticClass::class);

            yield Foo::class => diFactory(['factories.factory_none_static_class', 'create']);
        };

        $container = (new DiContainerBuilder(
            new DiContainerConfig(useZeroConfigurationDefinition: false)
        ))
            ->import('Tests\Integration\FactoryConfig\Fixtures\\', __DIR__.'/Fixtures')
            ->addDefinitions($config())
            ->build()
        ;

        self::assertEquals('Lorem ipsum non-static method', $container->get(Foo::class)->str);
        self::assertEquals('Lorem ipsum non-static method', $container->get(FooAttrThree::class)->str);
    }

    public function testFactoryInvokable(): void
    {
        $config = static function () {
            yield Foo::class => diFactory(FactoryInvokableClass::class);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($config())
            ->build()
        ;

        self::assertEquals('I am from invokable class', $container->get(Foo::class)->str);
        self::assertEquals('I am from invokable class', $container->get(FooAttrInvokable::class)->str);
    }

    public function testFactoryArgs(): void
    {
        $config = static function () {
            yield diAutowire(Bar::class)
                ->bindArguments('Lorem ipsum args')
            ;

            yield Foo::class => diFactory([FactoryClassArgs::class, 'create'])
                // `'value 1'` передача в параметр #1
                // `'value 2'` передача к параметру `$var2`
                // для параметра `$bar` выполнить разрешение на основе настроек контейнера
                ->bindArguments('value 1', var2: 'value 2')
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($config())
            ->build()
        ;

        self::assertEquals('value 1 | value 2 | Lorem ipsum args', $container->get(Foo::class)->str);
        self::assertEquals('value 2 | value 3 | Lorem ipsum args', $container->get(FooAttrArgs::class)->str);
    }

    public function testFactoryOnParams(): void
    {
        $config = static function (): Generator {
            yield diAutowire(Baz::class)
                ->bindArguments(
                    apiClient: diFactory(
                        [ApiClientFactory::class, 'createApiV2']
                    )
                )
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($config())
            ->build()
        ;

        self::assertInstanceOf(ApiClientInterface::class, $container->get(Baz::class)->apiClient);
        self::assertInstanceOf(ApiClientInterface::class, $container->get(BazAttr::class)->apiClient);
    }
}
