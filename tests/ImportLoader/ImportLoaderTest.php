<?php

declare(strict_types=1);

namespace Tests\ImportLoader;

use Kaspi\DiContainer\ImportLoader;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\ImportLoader
 */
class ImportLoaderTest extends TestCase
{
    public function testInitFinderFullyQualifiedNameLazy(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);
        $this->expectExceptionMessage('Need set source directory. Use method ImportLoader::setSrc().');

        (new ImportLoader())->getFullyQualifiedName('App\\')->valid();
    }
}
