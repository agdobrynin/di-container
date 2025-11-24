<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Fixtures\MultiAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\ImportLoader
 * @covers \Kaspi\DiContainer\ImportLoaderCollection
 *
 * @internal
 */
class MultiAutowireTest extends TestCase
{
    public function testMultiAutowireWithoutDefinitionsLoader(): void
    {
        $container = new DiContainer(
            definitions: [],
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: true,
                isSingletonServiceDefault: false,
            )
        );

        $m = $container->get(MultiAutowire::class);

        self::assertSame($m, $container->get(MultiAutowire::class));
        // definition key not defined.
        self::assertFalse($container->has('service.singleton'));
    }

    public function testMultiAutowireAndDefinitionsLoader(): void
    {
        $loader = (new DefinitionsLoader())
            ->import(
                namespace: 'Tests\Attributes\Fixtures\\',
                src: __DIR__.'/Fixtures',
                useAttribute: true,
            )
        ;

        $container = new DiContainer(
            definitions: $loader->definitions(),
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: true,
                isSingletonServiceDefault: false,
            )
        );

        $m = $container->get(MultiAutowire::class);
        // definition key load from DefinitionsLoader::import()
        $m1 = $container->get('service.singleton');

        self::assertSame($m, $container->get(MultiAutowire::class));
        self::assertSame($m1, $container->get('service.singleton'));
    }
}
