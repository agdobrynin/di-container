<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser;

use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;

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

    public function testCallableParserForCallableReady(): void
    {
        $res = $this->parseCallable(static fn () => 'ya');

        $this->assertIsCallable($res);
    }
}
