<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class ProxyClosureTest extends TestCase
{
    /**
     * @dataProvider successIdsDataProvider
     */
    public function testSuccess(string $id, string $expect): void
    {
        $asClosureAttr = new ProxyClosure($id);

        $this->assertEquals($expect, $asClosureAttr->getIdentifier());
    }

    public function successIdsDataProvider(): Generator
    {
        yield 'string' => ['ok', 'ok'];

        yield 'has start space' => [' ok', ' ok'];

        yield 'has start spaces' => [' ok ', ' ok '];

        yield 'one spaces' => [' ', ' '];
    }

    public function testFailure(): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('The $id parameter must be a non-empty string.');

        new ProxyClosure('');
    }
}
