<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use ArrayIterator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\FinderFullyQualifiedNameCollectionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\AppClass;
use Tests\DefinitionsLoader\Fixtures\Import\SubDirectory\Two;

use function array_keys;
use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;
use function sort;

use const T_TRAIT;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(Service::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(DefinitionsLoaderException::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(Helper::class)]
#[CoversClass(NotFoundException::class)]
class DefinitionsLoaderImportTest extends TestCase
{
    public function testImportMany(): void
    {
        $loader = (new DefinitionsLoader())
            ->import(
                'Tests\DefinitionsLoader\\',
                __DIR__.'/Fixtures/Import',
                excludeFilesRegExpPattern: [
                    '~Fixtures/Import/SubDirectory2/.+\.php$~',
                ],
            )
            ->import(
                'Tests\DefinitionsLoader\Fixtures\Import\SubDirectory2\\',
                __DIR__.'/Fixtures/Import/SubDirectory2/',
            )
            ->load(__DIR__.'/Fixtures/Import/services.php')
        ;

        $container = (new DiContainerFactory(
            new DiContainerConfig(
                isSingletonServiceDefault: false, // by default service none-singleton
            )
        ))->make($loader->definitions());

        $containerIds = array_keys(iterator_to_array($container->getDefinitions()));

        $expectContainerIds = [
            Fixtures\Import\SubDirectory\One::class,
            Two::class,
            Fixtures\Import\One::class,
            Fixtures\Import\Two::class,
            Fixtures\Import\TokenInterface::class,
            'services.two',
            Fixtures\Import\SubDirectory2\Three::class,
        ];

        sort($expectContainerIds);
        sort($containerIds);
        $this->assertEquals($expectContainerIds, $containerIds);

        // manual config in Fixtures/Import/services.php
        $this->assertEquals('baz-bar-foo', $container->get(Fixtures\Import\SubDirectory\One::class)->getToken());
        // import
        $this->assertInstanceOf(Two::class, $container->get(Two::class));
        // manual config in Fixtures/Import/services.php
        $this->assertEquals('foo-bar-baz', $container->get(Fixtures\Import\One::class)->getToken());
        // import
        $this->assertEquals('foo-bar-baz', $container->get(Fixtures\Import\Two::class)->getToken());
        // import and config by #[Service]
        $this->assertInstanceOf(Fixtures\Import\Two::class, $container->get(Fixtures\Import\TokenInterface::class));
        // class configured by #[Autowire]
        $serviceTwo = $container->get('services.two');
        $this->assertInstanceOf(Two::class, $serviceTwo);
        $this->assertSame($serviceTwo, $container->get('services.two'));
        $classTwo = $container->get(Two::class);
        $this->assertSame($classTwo, $container->get(Two::class));
    }

    public function testImportWithPreconfiguredImportLoader(): void
    {
        $loader = (new DefinitionsLoader(
            finderFullyQualifiedNameCollection: new FinderFullyQualifiedNameCollection()
        ))
            ->import(
                'Tests\DefinitionsLoader\\',
                __DIR__.'/Fixtures/Import',
                excludeFilesRegExpPattern: [
                    '~Fixtures/Import/SubDirectory2/.+\.php$~',
                    '~Fixtures/Import/SubDirectory/.+\.php$~',
                ],
            )
            ->import(
                'Tests\DefinitionsLoader\Fixtures\Import\SubDirectory2\\',
                __DIR__.'/Fixtures/Import/SubDirectory2/',
            )
            ->import(
                'Tests\DefinitionsLoader\Fixtures\Import\SubDirectory\\',
                __DIR__.'/Fixtures/Import/SubDirectory/',
            )
            ->load(__DIR__.'/Fixtures/Import/services.php')
        ;

        $container = (new DiContainerFactory(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false, // Not use finding class.
            )
        ))->make($loader->definitions());

        $this->assertTrue($container->has(Fixtures\Import\SubDirectory2\Three::class));
        $this->assertTrue($container->has(Two::class));
    }

    public function testImportWithoutUseAttributeForConfigureServices(): void
    {
        $loader = (new DefinitionsLoader())
            ->import(
                'Tests\DefinitionsLoader\\',
                __DIR__.'/Fixtures/Import',
                useAttribute: false
            )
        ;

        $container = (new DiContainerFactory(
            new DiContainerConfig(useAttribute: false),
        ))->make($loader->definitions());

        // resolve by useZeroConfigurationDefinition=true
        $this->assertTrue($container->has(Fixtures\Import\TokenInterface::class));

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Attempting to resolve interface');

        // import skip attribute Service on interface
        $container->get(Fixtures\Import\TokenInterface::class);
    }

    public function testImportAlreadyExists(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('The namespace "Tests\DefinitionsLoader\" has already been added to the import collection for source');

        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;

        $loader->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import');
    }

    public function testConflictConfigContainerIdentifierByAutowireAttributeAndConfig(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Container identifier "services.two" already registered');

        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
        $loader->addDefinitions(false, [
            'services.two' => static fn () => new ArrayIterator([]),
        ]);

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testConflictConfigContainerIdentifierByServiceAttributeAndConfig(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Container identifier "Tests\DefinitionsLoader\Fixtures\Import\TokenInterface" already registered');

        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
        $loader->addDefinitions(false, [
            diAutowire(Fixtures\Import\One::class)
                ->bindArguments(token: 'secure-token'),
            Fixtures\Import\TokenInterface::class => static fn (Fixtures\Import\One $one) => new Fixtures\Import\Two($one),
        ]);

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testCannotReflectClassFromImportedDefinition(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/Fixtures/ImportReflectionFail')
        ;

        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Reason: Interface "Tests\DefinitionsLoader\Fixtures\ImportReflectionFail\ContainerInterface" not found');

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testShortNamespaceAndClassName(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/../', excludeFilesRegExpPattern: [
                '~tests/_var/~',
                '~tests/([A-Z]+).*/~',
            ])
        ;

        $container = (new DiContainerFactory(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        ))->make($loader->definitions());

        $this->assertTrue($container->has(AppClass::class));
        $this->assertInstanceOf(AppClass::class, $container->get(AppClass::class));
    }

    public function testImportWhenFinderFullyQualifiedNameReturnNotValidToken(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported token id');

        $importLoaderMock = $this->createMock(FinderFullyQualifiedNameInterface::class);

        $importLoaderMock->method('get')
            ->willReturnCallback(
                function () {
                    yield 0 => [
                        'fqn' => 'Tests\SomeTrait',
                        'tokenId' => T_TRAIT,
                        'file' => 'tests/SomeTrait.php',
                        'line' => 3,
                    ];
                }
            )
        ;

        $importLoaderCollection = $this->createMock(FinderFullyQualifiedNameCollectionInterface::class);
        $importLoaderCollection->method('get')
            ->willReturnCallback(function () use ($importLoaderMock) {
                yield 'Tests\\' => $importLoaderMock;
            })
        ;

        (new DefinitionsLoader(finderFullyQualifiedNameCollection: $importLoaderCollection))
            ->import('Tests\\', __DIR__)
            ->definitions()
            ->current()
        ;
    }

    public function testFailParsePhpFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);

        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/PhpFileCannotParse')
        ;

        $loader->definitions()->valid();
    }
}
