<?php

declare(strict_types=1);

namespace Tests\ContainerReset;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\Parameter;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterWithContextAbstract;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function Kaspi\DiContainer\diAutowire;
use function random_bytes;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Parameter::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionParameter::class)]
#[CoversClass(DiDefinitionParameterWithContextAbstract::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(SourceParameterItem::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(TagsOnObjectDefinitionTrait::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
class ContainerResetTest extends TestCase
{
    #[DataProvider('dataProviderContainer')]
    public function testContainerRuntimeReset(DiContainerInterface $container): void
    {
        self::assertEquals('baz', $container->get(Foo::class)->baz->foo);

        self::assertFalse($container->has(Bar::class));

        self::assertEquals(
            [__CLASS__ => true],
            [...$container->getRemovedDefinitionIds()]
        );

        $container->set(Bar::class, diAutowire(Bar::class));

        self::assertTrue($container->has(Bar::class));
        self::assertInstanceOf(Bar::class, $container->get(Bar::class));

        self::assertFalse($container->parameters()->has('qux'));

        $container->parameters()->set('qux', 'quux');

        self::assertTrue($container->parameters()->has('qux'));
        self::assertEquals('quux', $container->parameters()->get('qux'));

        $container->reset();

        self::assertEquals('baz', $container->get(Foo::class)->baz->foo);
        self::assertFalse($container->has(Bar::class));
        self::assertFalse($container->parameters()->has('qux'));
    }

    public static function dataProviderContainer(): Generator
    {
        vfsStream::setup('test', structure: [
            'services.php' => '<?php
return static function (\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface $configurator) {
    $configurator->removeDefinition(Tests\ContainerReset\ContainerResetTest::class);    
};
            ',
            'parameters.php' => '<?php
return static function () {
    yield from [
        "foo" => "{bar}",
        "bar" => "baz",
        "bat" => true,
    ];   
};
            ',
        ]);

        yield 'runtime container' => [
            (new DiContainerBuilder())
                ->import('Tests\ContainerReset\\', __DIR__)
                ->load(vfsStream::url('test/services.php'))
                ->loadParameters(vfsStream::url('test/parameters.php'))
                ->build(),
        ];

        yield 'compiled container' => [
            (new DiContainerBuilder())
                ->import('Tests\ContainerReset\\', __DIR__)
                ->load(vfsStream::url('test/services.php'))
                ->loadParameters(vfsStream::url('test/parameters.php'))
                ->compileToFile(vfsStream::url('test/'), 'App\Container_'.bin2hex(random_bytes(5)), isExclusiveLockFile: false)
                ->build(),
        ];
    }
}

final class Foo
{
    public function __construct(public readonly Baz $baz) {}
}

#[AutowireExclude]
final class Bar {}

final class Baz
{
    public function __construct(
        #[Parameter]
        public readonly string $foo
    ) {}
}
