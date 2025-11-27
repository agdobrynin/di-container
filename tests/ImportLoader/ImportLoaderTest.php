<?php

declare(strict_types=1);

namespace Tests\ImportLoader;

use InvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\ImportLoader;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
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
        $this->expectExceptionMessageMatches('/Need set source directory. Use method .+ImportLoader::setSrc\(\)/');

        (new ImportLoader())->getSrc();
    }

    public function testConstructorProvideDependenciesCloneMethod(): void
    {
        $finderFile = new FinderFile();
        $finderFQN = new FinderFullyQualifiedName();

        $il = new ImportLoader(finderFile: $finderFile, finderFullyQualifiedName: $finderFQN);

        $new = clone $il;

        self::assertInstanceOf(FinderFullyQualifiedNameInterface::class, $new->getFinderFullyQualifiedName());
        self::assertNotSame($finderFile, $new->getFinderFullyQualifiedName());

        self::assertInstanceOf(FinderFileInterface::class, $new->getFinderFile());
        self::assertNotSame($finderFQN, $new->getFinderFile());
    }
}
