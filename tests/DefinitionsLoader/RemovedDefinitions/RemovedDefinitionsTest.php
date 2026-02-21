<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\RemovedDefinitions;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\Foo;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(Helper::class)]
class RemovedDefinitionsTest extends TestCase
{
    public function testGetExcludedDefinitionsFromConfiguredImport(): void
    {
        $loader = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import(
                'Tests\DefinitionsLoader\RemovedDefinitions\\',
                __DIR__.'/Fixtures',
                excludeFiles: [
                    __DIR__.'/Fixtures/Foo.php',
                    __DIR__.'/Fixtures/Qux/*',
                ]
            )
        ;

        // excluded classes
        self::assertSame(
            [
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\Foo',
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\Qux\Quux\Baz',
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\Qux\Bar',
            ],
            array_keys([...$loader->removedDefinitionIds()])
        );
        // available classes
        self::assertSame(
            [
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\SubDir\Foo',
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\Qux',
            ],
            array_keys([...$loader->definitions()])
        );
    }

    public function testGetExcludedDefinitionsWithoutImport(): void
    {
        $loader = (new DefinitionsLoader());

        self::assertEmpty([...$loader->removedDefinitionIds()]);
        // test cached imported via `DefinitionsLoader::$isRemovedDefinitionImport`
        self::assertEmpty([...$loader->removedDefinitionIds()]);
    }

    public function testInvalidNamespaceForExcludedDefinitions(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('$namespace must be end with symbol "\"');

        $loader = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import(
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures',
                __DIR__.'/Fixtures',
            )
        ;

        [...$loader->removedDefinitionIds()];
    }

    public function testExcludedDefinitionIsEmpty(): void
    {
        $loader = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import(
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\\',
                __DIR__.'/Fixtures',
                excludeFiles: [
                    __DIR__.'/Fixtures/NotExistDirectory/*',
                ]
            )
        ;

        self::assertEmpty([...$loader->removedDefinitionIds()]);
    }

    public function testIntersectDefinitions(): void
    {
        $loader = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import(
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\\',
                __DIR__.'/Fixtures',
                excludeFiles: [
                    __DIR__.'/Fixtures/Foo.php',
                ]
            )
        ;

        $loader->addDefinitions(true, [new DiDefinitionAutowire(Foo::class)]);

        self::assertEmpty([...$loader->removedDefinitionIds()]);
    }

    public function testResetRemovedDefinitions(): void
    {
        $loader = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import(
                'Tests\DefinitionsLoader\RemovedDefinitions\Fixtures\\',
                __DIR__.'/Fixtures',
                excludeFiles: [
                    __DIR__.'/Fixtures/Foo.php',
                ]
            )
        ;

        self::assertTrue($loader->removedDefinitionIds()->valid());
        $loader->reset();
        self::assertFalse($loader->removedDefinitionIds()->valid());
    }
}
