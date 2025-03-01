<?php

declare(strict_types=1);

namespace Tests\FinderFile;

use Kaspi\DiContainer\Finder\FinderFile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 *
 * @internal
 */
class FinderFileTest extends TestCase
{
    public static function dataProviderConstructor(): \Generator
    {
        yield 'none exist directory' => [
            __DIR__.'/no-dir',
            '/Cannot get by "\\\realpath\(\)".+\/no-dir/',
        ];

        yield 'none-directory' => [
            __FILE__,
            '/Argument \$src must be readable directory/',
        ];
    }

    /**
     * @dataProvider dataProviderConstructor
     */
    public function testFinderFileConstructorFail(string $src, string $expectMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($expectMessage);

        new FinderFile($src);
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
        $files = (new FinderFile(__DIR__.'/Fixtures', extension: 'txt'))->getFiles();

        $this->assertTrue($files->valid());
        $this->assertStringContainsString('tests/FinderFile/Fixtures/SomeFile.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('FinderFile/Fixtures/SubDirectoryTwo/SubSubDirectory/Doc.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }

    public function testFilesWithIgnoreExtension(): void
    {
        $files = (new FinderFile(__DIR__.'/Fixtures', extension: null))->getFiles();

        $this->assertTrue($files->valid());
        $this->assertStringContainsString('tests/FinderFile/Fixtures/FileOne.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/FileTwo.php', $files->current()->getRealPath());

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
            excludeRegExpPattern: ['~/Fixtures.+SubSubDirectory/~', '~/FileOne.php$~'],
            extension: null
        )
        )->getFiles();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SubDirectory/FileTwo.php', $files->current()->getRealPath());

        $files->next();

        $this->assertStringContainsString('tests/FinderFile/Fixtures/SomeFile.txt', $files->current()->getRealPath());

        $files->next();

        $this->assertFalse($files->valid());
    }
}
