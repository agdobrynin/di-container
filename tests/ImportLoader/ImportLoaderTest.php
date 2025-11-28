<?php

declare(strict_types=1);

namespace Tests\ImportLoader;

use InvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\ImportLoader;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\ImportLoader
 */
class ImportLoaderTest extends TestCase
{
    public function testInitFinderFullyQualifiedNameLazy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Need set source directory. Use method .+ImportLoader::setSrc\(\)/');

        (new ImportLoader())->getFullyQualifiedName('App\\')->valid();
    }

    public function testGetSrcFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Need set source directory\. Use method .+ImportLoader::setSrc\(\)/');

        (new ImportLoader())->getSrc();
    }

    public function testClone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Need set source directory\. Use method .+FinderFile::setSrc\(\)/');

        $il = (
            new ImportLoader(
                new FinderFile(),
                (new FinderFullyQualifiedName())->setNamespace('App\\')
            ))
                ->setSrc(__DIR__.'/Fixtures')
        ;

        $liClone = clone $il;

        $liClone->getFullyQualifiedName('Tests\ImportLoader\Fixtures\\')->valid();
    }
}
