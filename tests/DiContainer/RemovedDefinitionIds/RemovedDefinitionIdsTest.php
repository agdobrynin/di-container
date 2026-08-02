<?php

declare(strict_types=1);

namespace Tests\DiContainer\RemovedDefinitionIds;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ContainerParametersEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
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
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use Kaspi\DiContainer\Traits\FreezeTrait;
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
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(Helper::class)]
#[CoversClass(FinderClosureCode::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(FreezeTrait::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(\Kaspi\DiContainer\Compiler\Helper::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(ContainerParametersEntry::class)]
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(EventListener::class)]
class RemovedDefinitionIdsTest extends TestCase
{
    public function testRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
            removedDefinitionIds: [Foo::class]
        );

        self::assertTrue($container->has(Bar::class));
        self::assertFalse($container->has(Foo::class));
        self::assertSame(
            [Foo::class],
            [...$container->getRemovedDefinitionIds()]
        );
    }

    public function testDeferredRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            new DeferredSourceDefinitionsMutable(
                static fn () => [],
                static fn () => [Foo::class]
            ),
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
        );

        self::assertSame(
            [Foo::class],
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
            removedDefinitionIds: [Foo::class]
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
