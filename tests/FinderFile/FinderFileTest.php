<?php

declare(strict_types=1);

namespace Tests\FinderFile;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function array_intersect;
use function array_map;
use function count;
use function iterator_to_array;

/**
 * @internal
 */
#[CoversClass(FinderFile::class)]
class FinderFileTest extends TestCase
{
    #[DataProvider('dataProviderConstructor')]
    public function testFinderFileConstructorFail(string $src, string $expectMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectMessage);

        (new FinderFile($src))->getFiles()->current();
    }

    public static function dataProviderConstructor(): Generator
    {
        yield 'none exist directory' => [
            __DIR__.'/no-dir',
            'from parameter $src is invalid',
        ];

        yield 'none-directory' => [
            __FILE__,
            'from parameter $src must be readable',
        ];
    }

    public function testFilesWithPhpExtension(): void
    {
        $files = (new FinderFile(__DIR__.'/Fixtures'))->getFiles();

        $this->assertTrue($files->valid());
        $this->assertStringContainsString('tests/FinderFile/Fixtures/FileOne.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/FileTwo.php', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }

    public function testFilesWithTxtExtension(): void
    {
        $files = (new FinderFile(__DIR__.'/Fixtures', availableExtensions: ['txt']))->getFiles();

        $this->assertTrue($files->valid());
        $this->assertStringContainsString('tests/FinderFile/Fixtures/SomeFile.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('FinderFile/Fixtures/SubDirectoryTwo/SubSubDirectory/Doc.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }

    public function testFilesWithTxtAndDocExtension(): void
    {
        $files = (new FinderFile(__DIR__.'/Fixtures', availableExtensions: ['txt', 'doc']))
            ->getFiles()
        ;

        $expect = [
            'document.doc',
            'SomeFile.txt',
            'Doc.txt',
        ];

        $found = array_map(static fn (SplFileInfo $f) => $f->getFilename(), iterator_to_array($files));

        $this->assertCount(count($expect), $found);
        $this->assertSame($expect, $found);
    }

    public function testFilesWithIgnoreExtension(): void
    {
        $files = (new FinderFile(__DIR__.'/Fixtures', availableExtensions: []))->getFiles();

        $this->assertTrue($files->valid());
        $this->assertStringContainsString('tests/FinderFile/Fixtures/FileOne.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/FileTwo.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/document.doc', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SomeFile.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectoryTwo/SubSubDirectory/AnyFile', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectoryTwo/SubSubDirectory/Doc.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }

    public function testExcludeDirectoryAndFile(): void
    {
        $files = (new FinderFile(
            src: __DIR__.'/Fixtures',
            exclude: ['*Fixtures/*/SubSubDirectory/*', '*/FileOne.php', '*.doc'],
            availableExtensions: []
        ))
            ->getFiles()
        ;

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/FileTwo.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SomeFile.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }

    public function testExcludeFiles(): void
    {
        $finder = new FinderFile(
            src: __DIR__.'/Fixtures',
            exclude: ['*Fixtures/*/SubSubDirectory/*', '*/FileOne.php', '*.doc'],
            availableExtensions: []
        );

        $files = $finder->getFiles();
        $excludedFiles = $finder->getExcludedFiles();

        $intersectFiles = array_intersect(
            array_map(static fn ($file) => $file->getRealPath(), [...$files]),
            array_map(static fn ($file) => $file->getRealPath(), [...$excludedFiles]),
        );

        self::assertEmpty($intersectFiles);
    }

    public function testAsIsParameter(): void
    {
        $ff = new FinderFile('foo_baz', ['Kernel/*.php'], ['php', 'incl']);

        self::assertEquals('foo_baz', $ff->getSrc());
        self::assertEquals(['Kernel/*.php'], $ff->getExclude());
        self::assertEquals(['php', 'incl'], $ff->getAvailableExtensions());
    }
}
