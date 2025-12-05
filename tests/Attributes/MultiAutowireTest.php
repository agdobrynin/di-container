<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Fixtures\MultiAutowire;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(AttributeReader::class)]
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
