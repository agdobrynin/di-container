<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\Import\SubDirectory\Two;

use function Kaspi\DiContainer\diAutowire;

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
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedClassName
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class DefinitionsLoaderImportTest extends TestCase
{
    public function testImport(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
            ->load(__DIR__.'/Fixtures/Import/services.php')
        ;

        $container = (new DiContainerFactory(
            new DiContainerConfig(
                isSingletonServiceDefault: false, // by default service none-singleton
            )
        ))->make($loader->definitions());

        $containerIds = \array_keys(\iterator_to_array($container->getDefinitions()));

        $expectContainerIds = [
            Fixtures\Import\SubDirectory\One::class,
            Two::class,
            Fixtures\Import\One::class,
            Fixtures\Import\Two::class,
            Fixtures\Import\TokenInterface::class,
            'services.two',
        ];

        $this->assertCount(\count($expectContainerIds), $containerIds);
        $this->assertSame(\sort($expectContainerIds), \sort($containerIds));

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

    public function testImportAlreadyExists(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is already imported');

        $loader->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import');
    }

    public function testConflictConfigContainerIdentifierByAutowireAttributeAndConfig(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
        $loader->addDefinitions(false, [
            'services.two' => static fn () => new \ArrayIterator([]),
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
}
