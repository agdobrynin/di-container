<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DefinitionsConfigurator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\EventListener;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig\Bar;
use Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig\Foo;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(EventListener::class)]
#[CoversClass(DefinitionsConfigurator::class)]
class DefinitionsLoaderAutowireIsLazyTest extends TestCase
{
    #[RequiresPhp('< 8.4')]
    public function testAutoconfigureIsLazyPhpLess84(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('requires PHP version 8.4 or higher');

        (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig\\', __DIR__.'/Fixtures/AttributeIsLazyConfig')
            ->definitions()
            ->valid()
        ;
    }

    #[RequiresPhp('>= 8.4')]
    public function testAutoconfigureIsLazyPhp84OrHigher(): void
    {
        $definitions = [
            ...(new DefinitionsLoader())
                ->import('Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig\\', __DIR__.'/Fixtures/AttributeIsLazyConfig')
                ->definitions(),
        ];

        self::assertTrue($definitions[Bar::class]->isLazy());
        self::assertFalse($definitions[Foo::class]->isLazy());
    }
}
