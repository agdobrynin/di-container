<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Finder\FinderClass
 * @covers \Kaspi\DiContainer\Finder\FinderFile
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

        $container = (new DiContainerFactory())->make($loader->definitions());

        $classesNames = [];

        foreach ($container->getDefinitions() as $definition) {
            $classesNames[] = $definition->getDefinition()->getName();
        }

        $expect = [
            Fixtures\Import\SubDirectory\One::class,
            Fixtures\Import\SubDirectory\Two::class,
            Fixtures\Import\One::class,
            Fixtures\Import\Two::class,
            Fixtures\Import\TokenInterface::class,
        ];

        $this->assertCount(\count($expect), $classesNames);
        $this->assertSame(\sort($expect), \sort($classesNames));

        // manual config in Fixtures/Import/services.php
        $this->assertEquals('baz-bar-foo', $container->get(Fixtures\Import\SubDirectory\One::class)->getToken());
        // import
        $this->assertInstanceOf(Fixtures\Import\SubDirectory\Two::class, $container->get(Fixtures\Import\SubDirectory\Two::class));
        // manual config in Fixtures/Import/services.php
        $this->assertEquals('foo-bar-baz', $container->get(Fixtures\Import\One::class)->getToken());
        // import
        $this->assertEquals('foo-bar-baz', $container->get(Fixtures\Import\Two::class)->getToken());
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
}
