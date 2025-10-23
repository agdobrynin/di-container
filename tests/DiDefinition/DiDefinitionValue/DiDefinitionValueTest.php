<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionValue;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 *
 * @internal
 */
class DiDefinitionValueTest extends TestCase
{
    /**
     * @dataProvider dataProviderDiDefinitionValue
     */
    public function testDiDefinitionValue(mixed $definition, mixed $expect): void
    {
        $this->assertEquals($expect, (new DiDefinitionValue($definition))->getDefinition());
    }

    public function dataProviderDiDefinitionValue(): Generator
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
