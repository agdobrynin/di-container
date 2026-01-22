<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionValue;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(DiDefinitionValue::class)]
class DiDefinitionValueTest extends TestCase
{
    #[DataProvider('dataProviderDiDefinitionValue')]
    public function testDiDefinitionValue(mixed $definition, mixed $expect): void
    {
        $this->assertEquals($expect, (new DiDefinitionValue($definition))->getDefinition());
    }

    public static function dataProviderDiDefinitionValue(): Generator
    {
        yield 'set 1' => [null, null];

        yield 'set 2' => ['', ''];

        yield 'set 3' => ['foo', 'foo'];

        $o = new stdClass();
        $o->foo = 'bar';

        yield 'set 4' => [$o, $o];

        yield 'set 5' => [' ', ' '];
    }
}
