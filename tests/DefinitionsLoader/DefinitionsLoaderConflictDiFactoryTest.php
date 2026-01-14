<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\ConflictConfigureViaDiFactory\Foo;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(Helper::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
class DefinitionsLoaderConflictDiFactoryTest extends TestCase
{
    public function testConflictDiFactory(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('must be configure via php attribute or via config file');

        $definitions = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\Fixtures\ConflictConfigureViaDiFactory\\', __DIR__.'/Fixtures/ConflictConfigureViaDiFactory')
            ->addDefinitions(false, definitions: [
                diAutowire(Foo::class)
                    ->bindArguments('foo string'),
            ])
        ;

        $definitions->definitions()->valid();
    }
}
