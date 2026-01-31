<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportAutoconfigure;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(DefinitionsLoaderException::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionGet::class)]
class ImportAutoconfigureTest extends TestCase
{
    public function testAutoconfigure(): void
    {
        $container = (new DiContainerFactory(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false,
                useAttribute: false,
            )
        ))
            ->make(
                (new DefinitionsLoader())
                    ->import('Tests\\', __DIR__.'/Fixtures/')
                    ->definitions()
            )
        ;

        $this->assertEquals(
            ['name' => 'Ivan', 'surname' => 'Petrov', 'age' => 22],
            (array) $container->get(Fixtures\Person::class)
        );
    }

    public function testConflictAttributeAutowireExcludeAndConfigByDefinition(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessageMatches(
            '/mark as excluded via php attribute.+AutowireExclude.+Foo/'
        );

        $defs = (new DefinitionsLoader())
            ->addDefinitions(false, [
                diAutowire(Fixtures\Foo::class),
            ])
            ->import('Tests\\', __DIR__.'/Fixtures/')
            ->definitions()
        ;

        iterator_to_array($defs);
    }
}
