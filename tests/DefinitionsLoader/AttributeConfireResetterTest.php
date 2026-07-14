<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig\Bar;
use Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig\Foo;
use Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig\FooResetter;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiRuntime::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(BindArgumentsTrait::class)]
class AttributeConfireResetterTest extends TestCase
{
    public function testConfigureResetterViaAttribute(): void
    {
        $definitions = [...(new DefinitionsLoader())
            ->import(
                'Tests\\',
                __DIR__.'/Fixtures/AttributeResetterConfig',
                excludeFiles: [
                    __DIR__.'/Fixtures/AttributeResetterConfig/FooFailResetter.php',
                ]
            )
            ->definitions()];

        /** @var DiDefinitionAutowire $definition */
        $definition = $definitions[Foo::class];

        self::assertIsCallable($definition->getResetter());
        self::assertEquals([FooResetter::class, 'reset'], $definition->getResetter());

        /** @var DiDefinitionRuntime $definition */
        $definition = $definitions[Bar::class];

        self::assertEquals('reset', $definition->getResetter());
    }

    public function testConfigureResetterViaAttributeFail(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('($resetter) must be of type callable|string|false, array given');

        $definitions = (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/Fixtures/AttributeResetterConfig')
        ;

        [...$definitions->definitions()];
    }
}
