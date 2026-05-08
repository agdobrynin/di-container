<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Success\Bar;
use Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Success\Foo;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiRuntime::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(Helper::class)]
#[CoversFunction('Kaspi\DiContainer\diRuntime')]
class DefinitionsLoaderImportDiRuntimeTest extends TestCase
{
    public function testImportDiRuntimeSucceeds(): void
    {
        $loader = (new DefinitionsLoader())
            ->import(
                'Tests\DefinitionsLoader\ImportDiRuntime\\',
                __DIR__.'/Fixtures/Success',
            )
        ;

        $definitions = [...$loader->definitions()];

        self::assertCount(3, $definitions);

        self::assertEquals(Foo::class, $definitions[Foo::class]->getIdentifier());
        self::assertEquals(Bar::class, $definitions[Bar::class]->getIdentifier());
        self::assertEquals('services.bar', $definitions['services.bar']->getIdentifier());
    }

    public function testImportDiRuntimeAndDefinedDiRuntimeConflict(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically configure class.+Container identifier "services\.bar" already registered/');

        $loader = (new DefinitionsLoader())
            ->import(
                'Tests\DefinitionsLoader\ImportDiRuntime\\',
                __DIR__.'/Fixtures/Success',
            )
            ->addDefinitions(true, [
                \Kaspi\DiContainer\diRuntime('services.bar'),
            ])
        ;

        [...$loader->definitions()];
    }

    public function testImportDiRuntimeWithConflictOtherClassAttributes(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The attributes .+\\\Autowire and .+\\\DiRuntime cannot be declared together/');

        $loader = (new DefinitionsLoader())
            ->import(
                'Tests\DefinitionsLoader\ImportDiRuntime\\',
                __DIR__.'/Fixtures/Fail',
            )
        ;

        [...$loader->definitions()];
    }
}
