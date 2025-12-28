<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\ImportCreating\Foo;
use Throwable;

use function array_keys;
use function file_put_contents;
use function iterator_to_array;
use function sort;
use function unlink;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(Service::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversFunction('\Kaspi\DiContainer\diValue')]
#[CoversClass(DefinitionsLoaderException::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(Helper::class)]
class DefinitionLoaderImportCacheTest extends TestCase
{
    public function testImportCacheFileInNotReadableByDefinitions(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('isn\'t readable.');

        $f = vfsStream::newFile('i')
            ->chmod(0222)
            ->withContent('')->at(vfsStream::setup())
        ;

        (new DefinitionsLoader(importCacheFile: $f->url()))
            ->definitions()
            ->valid()
        ;
    }

    public function testImportCacheFileInNotReadableByImport(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('isn\'t readable.');

        $f = vfsStream::newFile('i')
            ->chmod(0222)
            ->withContent('')->at(vfsStream::setup())
        ;

        (new DefinitionsLoader(importCacheFile: $f->url()))
            ->import('App\\', __DIR__)
        ;
    }

    public function testImportCacheFileCannotCreated(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('isn\'t writable.');

        $dir = vfsStream::newDirectory('var', 0444)
            ->at(vfsStream::setup())
        ;

        (new DefinitionsLoader(importCacheFile: $dir->url().'/cache.php'))
            ->import('Tests\\', __DIR__.'/Fixtures/ImportCannotCreateCache')
            ->definitions()
            ->valid()
        ;
    }

    public function testImportCacheFileRead(): void
    {
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';
        @unlink($fileName);

        $content = '<?php
            return static function () {
                yield "data" => \Kaspi\DiContainer\diValue(["oka"]);
            };
        ';

        file_put_contents($fileName, $content);

        $definitions = (new DefinitionsLoader(importCacheFile: $fileName))
            ->import('App\\', __DIR__)
            ->definitions()
        ;

        $this->assertTrue($definitions->valid());
        $this->assertEquals(['oka'], $definitions->current()->getDefinition());

        @unlink($fileName);
    }

    public function testImportCacheFileCreating(): void
    {
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';
        @unlink($fileName);

        $definitions = (new DefinitionsLoader(importCacheFile: $fileName))
            ->import('Tests\DefinitionsLoader\Fixtures\ImportCreating\\', __DIR__.'/Fixtures/ImportCreating')
            ->definitions()
        ;

        $this->assertTrue($definitions->valid());
        $arr = iterator_to_array($definitions);

        $getKeys = array_keys($arr);
        $expectKeys = [
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\Interfaces\MagicInterface',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\Interfaces\OtherInterface',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\SubOne\Two',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\SubTwo\Three',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\One',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\Foo',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\Factory\FactoryFoo',
            'services.any',
        ];

        sort($getKeys);
        sort($expectKeys);

        $this->assertEquals($getKeys, $expectKeys);
        $this->assertTrue($arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\SubOne\Two']->isSingleton());

        $srvMI = $arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\Interfaces\MagicInterface'];
        $this->assertInstanceOf(DiDefinitionAutowire::class, $srvMI);
        $this->assertTrue($srvMI->isSingleton());

        $srvOI = $arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\Interfaces\OtherInterface'];
        $this->assertInstanceOf(DiDefinitionGet::class, $srvOI);
        $this->assertEquals('services.any', $srvOI->getDefinition());

        // Class Tests\DefinitionsLoader\Fixtures\ImportCreating\Bar autoconfigured via php attribute Autowire with container id = 'services.any'
        $this->assertEquals('Tests\DefinitionsLoader\Fixtures\ImportCreating\Bar', $arr['services.any']->getIdentifier());

        $this->assertNull($arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\One']->isSingleton());

        // test Factory on class
        /** @var DiDefinitionFactory $factory */
        $factory = $arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\Foo'];
        $this->assertInstanceOf(DiDefinitionFactory::class, $factory);
        $this->assertEquals('Tests\DefinitionsLoader\Fixtures\ImportCreating\Factory\FactoryFoo', $factory->getDefinition());

        /** @var Foo $resolveFactory */
        $resolveFactory = $factory->resolve($this->createMock(DiContainerInterface::class));
        $this->assertEquals('secure_string', $resolveFactory->secure);

        @unlink($fileName);
    }

    public function testImportCacheFileParseError(): void
    {
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';
        file_put_contents($fileName, '<?php return []');

        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('syntax error, unexpected end of file, expecting ";"');

        try {
            (new DefinitionsLoader(importCacheFile: $fileName))
                ->import('Tests\DefinitionsLoader\Fixtures\ImportCreating\\', __DIR__.'/Fixtures/ImportCreating')
                ->definitions()
                ->valid()
            ;
        } catch (Throwable $throwable) {
            throw $throwable;
        } finally {
            @unlink($fileName);
        }
    }

    public function testImportCacheFileError(): void
    {
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';
        file_put_contents($fileName, '<?php return ["a" => funcAAAAAAA()];');

        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Call to undefined function funcAAAAAAA()');

        try {
            (new DefinitionsLoader(importCacheFile: $fileName))
                ->import('Tests\DefinitionsLoader\Fixtures\ImportCreating\\', __DIR__.'/Fixtures/ImportCreating')
                ->definitions()
                ->valid()
            ;
        } catch (Throwable $throwable) {
            throw $throwable;
        } finally {
            @unlink($fileName);
        }
    }

    public function testImportCacheFileUnlinkWhenHasThrow(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Interface ".+ContainerInterface" not found/');

        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';

        (new DefinitionsLoader(importCacheFile: $fileName))
            ->import('Tests\DefinitionsLoader\Fixtures\ImportReflectionFail\\', __DIR__.'/Fixtures/ImportReflectionFail')
            ->definitions()
            ->valid()
        ;
    }
}
