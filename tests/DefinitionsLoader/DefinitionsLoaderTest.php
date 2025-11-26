<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Generator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Exception\ContainerIdentifierException
 * @covers \Kaspi\DiContainer\Exception\DefinitionsLoaderException
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class DefinitionsLoaderTest extends TestCase
{
    /**
     * @dataProvider dataProviderInvalidContent
     */
    public function testInvalidFileContent(string $file): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file');

        (new DefinitionsLoader())->load($file);
    }

    public function dataProviderInvalidContent(): Generator
    {
        yield 'no return' => [__DIR__.'/Fixtures/FailContent/f1.php'];

        yield 'none php' => [__DIR__.'/Fixtures/FailContent/f2.txt'];
    }

    public function testFileNotFound(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        (new DefinitionsLoader())->load('f.php');
    }

    /**
     * @dataProvider dataProvideDefinitionException
     */
    public function testDefinitionException(string $file): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file "'.$file.'".');

        (new DefinitionsLoader())->load($file);
    }

    public function dataProvideDefinitionException(): Generator
    {
        yield 'definition without container identifier' => [__DIR__.'/Fixtures/DefinitionException/no-identifier.php'];

        yield 'definition empty identifier' => [__DIR__.'/Fixtures/DefinitionException/empty-identifier.php'];
    }

    public function testOverrideDefinitionException(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file');

        (new DefinitionsLoader())->load(
            __DIR__.'/Fixtures/config1.php',
            __DIR__.'/Fixtures/config2.php'
        );
    }

    public function testOverrideDefinition(): void
    {
        $loader = (new DefinitionsLoader())->load(__DIR__.'/Fixtures/config1.php');
        $loader->loadOverride(__DIR__.'/Fixtures/config2.php');

        $this->assertEquals('ok2', $loader->definitions()->current()());
    }

    public function testContainerIdentifierAlreadyRegistered(): void
    {
        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);

        $config = static function (): Generator {
            yield 'services.foo' => 'foo';

            yield 'services.bar' => 'bar';
        };

        $config2 = static function (): Generator {
            yield 'services.foo' => 'baz';
        };

        $def = (new DefinitionsLoader())->addDefinitions(false, $config());
        $def->addDefinitions(false, $config2());
    }
}
