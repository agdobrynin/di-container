<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProxyClosure::class)]
#[CoversClass(Helper::class)]
class ProxyClosureTest extends TestCase
{
    #[DataProvider('successIdsDataProvider')]
    public function testSuccess(string $id, string $expect): void
    {
        $asClosureAttr = new ProxyClosure($id);

        $this->assertEquals($expect, $asClosureAttr->id);
    }

    public static function successIdsDataProvider(): Generator
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
