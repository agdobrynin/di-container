<?php

declare(strict_types=1);

namespace Tests\DiContainer\RemovedDefinitionIds;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainer\RemovedDefinitionIds\Fixtures\Bar;
use Tests\DiContainer\RemovedDefinitionIds\Fixtures\Foo;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(CompiledEntries::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
class RemovedDefinitionIdsTest extends TestCase
{
    public function testRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
            removedDefinitionIds: [Foo::class => true]
        );

        self::assertTrue($container->has(Bar::class));
        self::assertFalse($container->has(Foo::class));
        self::assertSame(
            [Foo::class => true],
            [...$container->getRemovedDefinitionIds()]
        );
    }

    public function testDeferredRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            new DeferredSourceDefinitionsMutable(
                static fn () => [],
                static fn () => [Foo::class => true]
            ),
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
        );

        self::assertSame(
            [Foo::class => true],
            [...$container->getRemovedDefinitionIds()]
        );
    }

    public function testResolveRemovedDefinition(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
            removedDefinitionIds: [Foo::class => true]
        );

        $container->get(Foo::class);
    }

    public function testResolveRemovedDefinitionOnCompiledContainer(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        vfsStream::setup();

        $container = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            )
        ))
            ->import(
                'Tests\DiContainer\RemovedDefinitionIds\Fixtures\\',
                __DIR__.'/Fixtures',
                excludeFiles: [
                    '*/Foo.php',
                ]
            )
            ->compileToFile(
                vfsStream::url('root/'),
                'App\Container'.bin2hex(random_bytes(5)),
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        $container->get(Foo::class);
    }
}
