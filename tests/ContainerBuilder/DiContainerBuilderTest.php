<?php

declare(strict_types=1);

namespace Tests\ContainerBuilder;

use Generator;
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
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\ContainerBuilder\Fixtures\Foo;

use function bin2hex;
use function Kaspi\DiContainer\diAutowire;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(Helper::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(CompiledEntries::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
class DiContainerBuilderTest extends TestCase
{
    public function testDefinitionLoaderImportThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build container while import files from directory "/var/cache" with namespace "App\" using method Kaspi\DiContainer\DiContainerBuilder::import().');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('import')
            ->willThrowException(new DefinitionsLoaderException())
        ;
        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->import('App\\', '/var/cache')
            ->build()
        ;
    }

    public function testDefinitionLoaderGetDefinitionThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build runtime container. Caused by:');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('definitions')
            ->willThrowException(new DefinitionsLoaderException())
        ;

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->build()
        ;
    }

    public function testDefinitionLoaderLoadFromFileWithOverrideThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build container while load configuration from file "/www/app/config/services.php" using method Kaspi\DiContainer\DiContainerBuilder::loadOverride().');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('loadOverride')
            ->willThrowException(new DefinitionsLoaderException())
        ;

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->loadOverride('/www/app/config/services.php')
            ->build()
        ;
    }

    public function testDefinitionLoaderLoadFromFileThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build container while load configuration from file "/www/app/config/services.php" using method Kaspi\DiContainer\DiContainerBuilder::load().');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('load')
            ->willThrowException(new DefinitionsLoaderException())
        ;

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->load('/www/app/config/services.php')
            ->build()
        ;
    }

    public function testDefinitionLoaderLoadFromUserDefinitionOverrideThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build container while add definition using method Kaspi\DiContainer\DiContainerBuilder::addDefinitionsOverride().');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('addDefinitions')
            ->willThrowException(new DefinitionsLoaderException())
        ;

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->addDefinitionsOverride(['foo' => 'bar'])
            ->build()
        ;
    }

    public function testDefinitionLoaderLoadFromUserDefinitionThrowException(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build container while add definition using method Kaspi\DiContainer\DiContainerBuilder::addDefinitions().');

        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);
        $defLoader->method('addDefinitions')
            ->willThrowException(new DefinitionsLoaderException())
        ;

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->addDefinitions(['foo' => 'bar'])
            ->build()
        ;
    }

    public function testWrongCompiledContainerClassName(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid class name for compiled container.');

        vfsStream::setup();
        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->compileToFile(vfsStream::url('root'), 'App\Core\123Class')
            ->build()
        ;
    }

    public function testFailCompileToFile(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Failed to write to "vfs://root/Container.php"');

        vfsStream::setup();
        $defLoader = $this->createMock(DefinitionsLoaderInterface::class);

        (new DiContainerBuilder(definitionsLoader: $defLoader))
            ->compileToFile(vfsStream::url('root'), 'App\Core\Container')
            ->build()
        ;
    }

    public function testSuccessBuildRuntimeContainer(): void
    {
        $appConfiguredDefinitions = static function (): Generator {
            yield diAutowire(Foo::class)
                ->bindArguments('baz')
            ;
        };

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerNullConfig()
        ))
            ->addDefinitions($appConfiguredDefinitions())
            ->build()
        ;

        self::assertEquals('baz', $container->get(Foo::class)->bar);
    }

    public function testSuccessCompileContainer(): void
    {
        vfsStream::setup();

        $appConfiguredDefinitions = static function (): Generator {
            yield diAutowire(Foo::class)
                ->bindArguments('baz')
            ;
        };

        $builder = (new DiContainerBuilder(containerConfig: new DiContainerNullConfig()))
            ->addDefinitions($appConfiguredDefinitions())
        ;
        $containerSuffix = bin2hex(random_bytes(5));

        $builder->compileToFile(vfsStream::url('root'), 'App\Core\Container'.$containerSuffix, isExclusiveLockFile: false);

        $container = $builder->build();

        self::assertEquals('baz', $container->get(Foo::class)->bar);
        self::assertInstanceOf('\App\Core\Container'.$containerSuffix, $container);
    }

    public function testVariadicParamOnLoad(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php return ["foo" => "bar"];',
            'config2.php' => '<?php return ["baz" => "qux"];',
        ]);

        $container = (new DiContainerBuilder(containerConfig: new DiContainerNullConfig()))
            ->load(
                vfsStream::url('root/config1.php'),
                vfsStream::url('root/config2.php'),
            )
            ->build()
        ;

        self::assertEquals('bar', $container->get('foo'));
        self::assertEquals('qux', $container->get('baz'));
    }

    public function testVariadicParamOnLoadOverride(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php return ["foo" => "bar"];',
            'config2.php' => '<?php return ["foo" => "qux"];',
        ]);

        $container = (new DiContainerBuilder(containerConfig: new DiContainerNullConfig()))
            ->loadOverride(
                vfsStream::url('root/config1.php'),
                vfsStream::url('root/config2.php'),
            )
            ->build()
        ;

        self::assertEquals('qux', $container->get('foo'));
    }
}
