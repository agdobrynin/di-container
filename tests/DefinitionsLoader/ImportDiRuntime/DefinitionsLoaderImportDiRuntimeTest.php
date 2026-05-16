<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use org\bovigo\vfs\vfsStream;
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
#[CoversClass(Tag::class)]
#[CoversClass(TagsTrait::class)]
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

    public function testConfiguratorFindTagged(): void
    {
        vfsStream::setup('root', structure: [
            'services.php' => '<?php
return static function (\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface $configurator) {
    foreach ($configurator->findTaggedDefinition("tags.bar_service") as $definition) {
        $definition->bindTag("tags.config");
    }
};',
        ]);
        $loader = (new DefinitionsLoader())
            ->useAttribute(true)
            ->import(
                'Tests\DefinitionsLoader\ImportDiRuntime\\',
                __DIR__.'/Fixtures/Success',
            )
            ->load(vfsStream::url('root/services.php'))
        ;

        $defs = [...$loader->definitions()];

        $config = $this->createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')->willReturn(true);

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('getConfig')
            ->willReturn($config)
        ;

        self::assertCount(3, $defs);

        $foo = $defs[Foo::class]->setContainer($container);

        self::assertFalse($foo->hasTag('tags.config'));
        self::assertTrue($foo->hasTag('tags.foo_service'));

        self::assertTrue($defs[Bar::class]->setContainer($container)->hasTag('tags.config'));
        self::assertTrue($defs['services.bar']->setContainer($container)->hasTag('tags.config'));
    }
}
