<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class DiDefinitionAutowireTest extends TestCase
{
    public function testInvokeMethodWithWringParameterName(): void
    {
        $this->expectException(\Error::class);

        (new DiDefinitionAutowire('a', self::class, false, []))->invoke(['a' => 1, 'b' => 'aaa']);
    }

    public function testDefinitionMethod(): void
    {
        $d = new DiDefinitionAutowire('a', self::class, false, []);

        $this->assertEquals(self::class, $d->getDefinition());
    }

    public function testInvokeMethodEmptyConstructor(): void
    {
        $class = new class() {};

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('not have a constructor');

        (new DiDefinitionAutowire('a', $class::class, false, []))->invoke(['a' => 'abc']);
    }
}
