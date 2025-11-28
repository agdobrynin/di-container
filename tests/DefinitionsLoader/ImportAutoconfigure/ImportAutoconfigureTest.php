<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportAutoconfigure;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 * @covers \Kaspi\DiContainer\Exception\DefinitionsLoaderException
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\ImportLoader
 * @covers \Kaspi\DiContainer\ImportLoaderCollection
 */
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
