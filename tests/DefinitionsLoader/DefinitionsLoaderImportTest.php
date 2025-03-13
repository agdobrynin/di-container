<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use ArrayIterator;
use InvalidArgumentException;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Tests\AppClass;
use Tests\DefinitionsLoader\Fixtures\Import\SubDirectory\Two;

use function array_keys;
use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;
use function sort;

use const T_TRAIT;

/**
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class DefinitionsLoaderImportTest extends TestCase
{
    public function testImport(): void
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
        $this->expectExceptionMessage('Definition not found for identifier');
        // import skip attribute Service on interface
        $container->get(Fixtures\Import\TokenInterface::class);
    }

    public function testImportAlreadyExists(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is already imported');

        $loader->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import');
    }

    public function testConflictConfigContainerIdentifierByAutowireAttributeAndConfig(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
        $loader->addDefinitions(false, [
            'services.two' => static fn () => new ArrayIterator([]),
        ]);

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically set definition via #\[.+Autowire\].+"services\.two".+/');

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testConflictConfigContainerIdentifierByServiceAttributeAndConfig(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
        $loader->addDefinitions(false, [
            diAutowire(Fixtures\Import\One::class)
                ->bindArguments(token: 'secure-token'),
            Fixtures\Import\TokenInterface::class => static fn (Fixtures\Import\One $one) => new Fixtures\Import\Two($one),
        ]);

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically set definition via #\[.+Service\].+".+\\\TokenInterface".+/');

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testCannotReflectClassFromImportedDefinition(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/Fixtures/ImportReflectionFail')
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reason: Interface "Tests\DefinitionsLoader\Fixtures\ImportReflectionFail\ContainerInterface" not found');

        (new DiContainerFactory())->make($loader->definitions());
    }

    public function testShortNamespaceAndClassName(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/../', excludeFilesRegExpPattern: [
                '~tests/Attributes~',
                '~tests/D.+~',
                '~tests/F.+~',
                '~tests/L.+~',
                '~tests/T.+~',
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
        $finderFile = $this->createMock(FinderFileInterface::class);

        $finderFQN = $this->createMock(FinderFullyQualifiedNameInterface::class);
        $finderFQN->method('setNamespace')
            ->willReturn($finderFQN)
        ;
        $finderFQN->method('setFiles')
            ->willReturn($finderFQN)
        ;
        $finderFQN
            ->method('find')
            ->willReturnCallback(static function () {
                yield ['fqn' => 'Tests\SomeTrait', 'tokenId' => T_TRAIT];
            })
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported token id');

        (new DefinitionsLoader())
            ->import('Tests\\', __DIR__.'/../', finderFile: $finderFile, finderFullyQualifiedName: $finderFQN)
            ->definitions()
            ->current()
        ;
    }
}
