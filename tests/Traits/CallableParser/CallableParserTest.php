<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\CallableParser\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Traits\CallableParserTrait
 *
 * @internal
 */
class CallableParserTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use CallableParserTrait;
    use PsrContainerTrait; // 🧨 need for abstract method getContainer in CallableParserTrait.

    public function testDefinitionIsCallableReady(): void
    {
        $res = $this->parseCallable(static fn () => 'ya');

        $this->assertIsCallable($res);
    }

    public function testDefinitionArrayEmpty(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('two array elements must be provided');

        $this->parseCallable([]);
    }

    public function testDefinitionArrayOneItem(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('two array elements must be provided');

        $this->parseCallable(['one']);
    }

    public function testDefinitionIsCallableString(): void
    {
        $res = $this->parseCallable(SuperClass::class.'::staticMethod');

        $this->assertEquals(SuperClass::class.'::staticMethod', $res);
    }
}
