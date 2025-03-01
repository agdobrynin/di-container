<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DefinitionsLoaderInportTest extends TestCase
{
    public function testImport(): void
    {
        $loader = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\\', __DIR__.'/Fixtures/Import')
        ;
    }
}
