<?php

declare(strict_types=1);

namespace Tests\ImportLoaderCollection;

use Kaspi\DiContainer\ImportLoader;
use Kaspi\DiContainer\ImportLoaderCollection;
use PHPUnit\Framework\TestCase;

/**
 * @implements
 *
 * @covers \Kaspi\DiContainer\Finder\FinderFile
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 * @covers \Kaspi\DiContainer\ImportLoader
 * @covers \Kaspi\DiContainer\ImportLoaderCollection
 *
 * @internal
 */
class ImportLoaderCollectionTest extends TestCase
{
    public function testClone(): void
    {
        $collection = (new ImportLoaderCollection(new ImportLoader()))
            ->importFromNamespace('Tests\ImportLoaderCollection\\', __DIR__.'/Fixtures')
        ;

        $this->assertTrue($collection->getImportLoaders()->valid());

        $collectionClone = clone $collection;

        $this->assertFalse($collectionClone->getImportLoaders()->valid());
    }
}
