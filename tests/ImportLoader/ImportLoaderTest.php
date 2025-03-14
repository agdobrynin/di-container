<?php

declare(strict_types=1);

namespace Tests\ImportLoader;

use InvalidArgumentException;
use Kaspi\DiContainer\ImportLoader;
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Need set source directory. Use method ImportLoader::setSrc().');

        (new ImportLoader())->getFullyQualifiedName('App\\')->valid();
    }
}
