<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiDefinition\DiDefinitionClosure;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionClosure
 *
 * @internal
 */
class DiDefinitionClosureTest extends TestCase
{
    public function testGetDefinition(): void
    {
        $d = new DiDefinitionClosure('x', static function (): string { return 'aaaa'; }, true, []);

        $this->assertInstanceOf(\Closure::class, $d->getDefinition());
    }

    public function testInvoke(): void
    {
        $d = new DiDefinitionClosure('x', static function (string ...$words): string { return \implode('_', $words); }, true, []);

        $this->assertEquals('hello_world', $d->invoke(['hello', 'world']));
    }
}
