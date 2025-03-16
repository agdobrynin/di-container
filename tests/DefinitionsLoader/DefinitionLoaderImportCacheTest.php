<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

use function array_keys;
use function file_put_contents;
use function iterator_to_array;
use function sort;
use function unlink;

/**
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DefinitionsLoader
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\ImportLoader
 * @covers \Kaspi\DiContainer\ImportLoaderCollection
 *
 * @internal
 */
class DefinitionLoaderImportCacheTest extends TestCase
{
    public function testImportCacheFileInNotReadableByDefinitions(): void
    {
        $f = vfsStream::newFile('i')
            ->chmod(0222)
            ->withContent('')->at(vfsStream::setup())
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cache file for imported definitions via DefinitionsLoader::import() is not readable');

        (new DefinitionsLoader(importCacheFile: $f->url()))
            ->definitions()
            ->valid()
        ;
    }

    public function testImportCacheFileInNotReadableByImport(): void
    {
        $f = vfsStream::newFile('i')
            ->chmod(0222)
            ->withContent('')->at(vfsStream::setup())
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cache file for imported definitions via DefinitionsLoader::import() is not readable');

        (new DefinitionsLoader(importCacheFile: $f->url()))
            ->import('App\\', __DIR__)
        ;
    }

    public function testImportCacheFileCannotCreated(): void
    {
        $dir = vfsStream::newDirectory('var', 0444)
            ->at(vfsStream::setup())
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot create cache file');

        (new DefinitionsLoader(importCacheFile: $dir->url().'/cache.php'))
            ->import('App\\', __DIR__)
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
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\SubOne\Two',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\SubTwo\Three',
            'Tests\DefinitionsLoader\Fixtures\ImportCreating\One',
        ];

        sort($getKeys);
        sort($expectKeys);

        $this->assertEquals($getKeys, $expectKeys);
        $this->assertTrue($arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\SubOne\Two']->isSingleton());
        $this->assertNull($arr['Tests\DefinitionsLoader\Fixtures\ImportCreating\One']->isSingleton());

        @unlink($fileName);
    }

    public function testImportCacheFileParseError(): void
    {
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';
        file_put_contents($fileName, '<?php return []');

        $this->expectException(RuntimeException::class);
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

        $this->expectException(RuntimeException::class);
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
        $fileName = __DIR__.'/../_var/cache/'.__FUNCTION__.'_cache.php';

        $this->expectException(RuntimeException::class);

        (new DefinitionsLoader(importCacheFile: $fileName))
            ->import('Tests\DefinitionsLoader\Fixtures\ImportReflectionFail\\', __DIR__.'/Fixtures/ImportReflectionFail')
            ->definitions()
            ->valid()
        ;
    }
}
