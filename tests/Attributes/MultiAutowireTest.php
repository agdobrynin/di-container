<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Fixtures\Bar;
use Tests\Attributes\Fixtures\Foo;
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
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiContainerBuilder::class)]
class MultiAutowireTest extends TestCase
{
    public function testMultiAutowireContainer(): void
    {
        $container = new DiContainer(
            definitions: [],
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: true,
                isSingletonServiceDefault: false,
            )
        );

        self::assertInstanceOf(Foo::class, $container->get(MultiAutowire::class)->qux);
        // definition key not defined.
        self::assertFalse($container->has('service.multi_bar'));
    }

    public function testMultiAutowireAndDefinitionsLoader(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true,
            isSingletonServiceDefault: false,
        );
        $container = (new DiContainerBuilder(containerConfig: $config))
            ->import(
                namespace: 'Tests\Attributes\Fixtures\\',
                src: __DIR__.'/Fixtures',
            )
            ->build()
        ;

        self::assertInstanceOf(Foo::class, $container->get(MultiAutowire::class)->qux);
        self::assertInstanceOf(Bar::class, $container->get('service.multi_bar')->qux);
    }
}
