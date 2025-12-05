<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionReference;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
class DiDefinitionReferenceTest extends TestCase
{
    public function testDiDefinitionReferenceFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        (new DiDefinitionGet(''))->getDefinition();
    }

    #[DataProvider('dataProviderDefinitionSuccess')]
    public function testDiDefinitionReferenceSuccess(string $definition, string $expect): void
    {
        $def = new DiDefinitionGet($definition);

        $this->assertEquals($expect, $def->getDefinition());
    }

    public static function dataProviderDefinitionSuccess(): Generator
    {
        yield 'set 1' => ['key1', 'key1'];

        yield 'set 2' => ['   key2', '   key2'];

        yield 'set 3' => ['   key3   ', '   key3   '];

        yield 'set 4' => ['key4   ', 'key4   '];
    }
}
