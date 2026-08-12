<?php

declare(strict_types=1);

namespace Tests\Compiler\CompileLazyObject;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
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
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\SetupAttributeTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Compiler\CompileLazyObject\Fixtures\Bar;
use Tests\Compiler\CompileLazyObject\Fixtures\Foo;
use Tests\Compiler\CompileLazyObject\Fixtures\Qux;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[RequiresPhp('>= 8.4')]
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(ContainerParametersEntry::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntries::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(Helper::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderClosureCode::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(TagsTrait::class)]
#[CoversClass(FreezeTrait::class)]
#[CoversClass(SetupAttributeTrait::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(EventListener::class)]
#[CoversClass(DefinitionsConfigurator::class)]
class CompileLazyObjectTest extends TestCase
{
    public function testCompileLazyObject(): void
    {
        $containerClass = 'Container'.bin2hex(random_bytes(8));
        vfsStream::setup();
        $container = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->compileToFile(
                vfsStream::url('root/'),
                'App\\'.$containerClass,
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        $foo = $container->get(Foo::class);

        $reflectorFoo = new ReflectionClass(Foo::class);

        $reflectorBar = new ReflectionClass(Bar::class);

        self::assertTrue($reflectorBar->isUninitializedLazyObject($foo->getBar()));
        self::assertEquals('Lorem ipsum in Bar', $foo->getBar()->getVal());
        self::assertFalse($reflectorBar->isUninitializedLazyObject($foo->getBar()));

        $reflectorQux = new ReflectionClass(Qux::class);

        self::assertTrue($reflectorQux->isUninitializedLazyObject($foo->getQux()));
        self::assertEquals('Lorem ipsum in Qux', $foo->getQux()->getVal());
        self::assertFalse($reflectorQux->isUninitializedLazyObject($foo->getQux()));
    }
}
