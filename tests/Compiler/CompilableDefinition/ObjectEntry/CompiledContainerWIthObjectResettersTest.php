<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
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
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\ObjectResetters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\ResetterTrait;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile\Bar;
use Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile\Baz;
use Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile\Foo;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntries::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompilerToFile::class)]
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(Helper::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderClosureCode::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(ObjectResetters::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(ResetterTrait::class)]
#[CoversClass(DiContainerBuilder::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(FreezeTrait::class)]
class CompiledContainerWIthObjectResettersTest extends TestCase
{
    public function testObjectResettersCompilation(): void
    {
        vfsStream::setup(structure: [
            'config.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile\{Bar, Baz};

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    yield diAutowire(Bar::class, isSingleton: true)
        // Для сброса вызвать callback функцию
        ->setResetter(static function (Bar $bar): void {
            ((fn () => $this->bar = "reset bar")
                ->bindTo($bar, $bar))()
                ;
        });
        
    yield diAutowire(Baz::class, isSingleton: true);
};
',
        ]);

        $container = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/FixturesForCompile')
            ->load(vfsStream::url('root/config.php'))
            ->compileToFile(
                vfsStream::url('root'),
                'App\Container'.bin2hex(random_bytes(5)),
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        self::assertEquals('Lorem ipsum bar', $container->get(Bar::class)->getBar());
        self::assertEquals('Lorem ipsum baz', $container->get(Baz::class)->getBaz());
        self::assertEquals('Lorem ipsum foo', $container->get(Foo::class)->getFoo());

        $container->get(ObjectResetters::class)->reset();

        self::assertEquals('reset bar', $container->get(Bar::class)->getBar());
        self::assertEquals('reset baz', $container->get(Baz::class)->getBaz());
        self::assertEquals('reset foo', $container->get(Foo::class)->getFoo());
    }

    public function testObjectResettersManuallyConfigCompilation(): void
    {
        vfsStream::setup(structure: [
            'config.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\ObjectResetters;
use Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile\{Bar, Baz, Foo, FooResetter};

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    $resetters = [
        Bar::class => static function (Bar $bar): void {
            ((fn () => $this->bar = "manually bar")
                ->bindTo($bar, $bar))()
                ;
        },
        Baz::class => "reset",
        Foo::class => [FooResetter::class, "doReset"],
    ];
    yield diAutowire(ObjectResetters::class, true)
        ->setup("setup",[$resetters]);
};
',
        ]);

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                isSingletonServiceDefault: true,
                isConfigureObjectResettersFromDefinitions: false
            )
        ))
            ->import('Tests\\', __DIR__.'/FixturesForCompile')
            ->load(vfsStream::url('root/config.php'))
            ->compileToFile(
                vfsStream::url('root'),
                'App\Container'.bin2hex(random_bytes(5)),
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        self::assertEquals('Lorem ipsum bar', $container->get(Bar::class)->getBar());
        self::assertEquals('Lorem ipsum baz', $container->get(Baz::class)->getBaz());
        self::assertEquals('Lorem ipsum foo', $container->get(Foo::class)->getFoo());

        $container->get(ObjectResetters::class)->reset();

        self::assertEquals('manually bar', $container->get(Bar::class)->getBar());
        self::assertEquals('reset baz', $container->get(Baz::class)->getBaz());
        self::assertEquals('manually foo', $container->get(Foo::class)->getFoo());
    }
}
