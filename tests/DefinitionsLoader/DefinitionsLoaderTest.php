<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Generator;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(ContainerIdentifierException::class)]
#[CoversClass(DefinitionsLoaderException::class)]
#[CoversClass(Helper::class)]
class DefinitionsLoaderTest extends TestCase
{
    #[DataProvider('dataProviderInvalidContent')]
    public function testInvalidFileContent(string $file): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file');

        (new DefinitionsLoader())->load($file);
    }

    public static function dataProviderInvalidContent(): Generator
    {
        yield 'no return' => [__DIR__.'/Fixtures/FailContent/f1.php'];

        yield 'none php' => [__DIR__.'/Fixtures/FailContent/f2.txt'];
    }

    public function testFileNotFound(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('does not exist or isn\'t readable');

        (new DefinitionsLoader())->load('f.php');
    }

    #[DataProvider('dataProvideDefinitionException')]
    public function testDefinitionException(string $file): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Invalid definition in file "'.$file.'".');

        (new DefinitionsLoader())->load($file);
    }

    public static function dataProvideDefinitionException(): Generator
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

    public function testCannotParseConfigFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);

        vfsStream::setup(structure: [
            'config1.php' => '<?php return [ "foo" => $ ];',
        ]);

        (new DefinitionsLoader())
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
            ->valid()
        ;
    }
}
