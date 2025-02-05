<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod
 *
 * @internal
 */
class TaggedDefaultPriorityMethodTest extends TestCase
{
    public static function dataProvider(): \Generator
    {
        yield 'empty string' => [''];

        yield 'string' => ['getPriorityFn'];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTaggedDefaultPriorityMethod(string $methodName): void
    {
        $this->assertEquals($methodName, (new TagDefaultPriorityMethod($methodName))->getIdentifier());
    }
}
