<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(Service::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(Helper::class)]
class DefinitionsLoaderResetTestTest extends TestCase
{
    public function testReset(): void
    {
        $loader = (new DefinitionsLoader())
            ->addDefinitions(false, ['foo' => 'bar'])
            ->addDefinitions(false, ['baz' => 'qux'])
            ->import('Tests\DefinitionsLoader\Fixtures\Import\\', __DIR__.'/Fixtures/Import')
        ;

        self::assertTrue($loader->definitions()->valid());

        $loader->reset();

        self::assertFalse($loader->definitions()->valid());
    }
}
