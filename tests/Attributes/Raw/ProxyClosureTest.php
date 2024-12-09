<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 *
 * @internal
 */
class ProxyClosureTest extends TestCase
{
    public function successIdsDataProvider(): \Generator
    {
        yield 'string' => ['ok', 'ok'];

        yield 'has start space' => [' ok', ' ok'];

        yield 'has start spaces' => [' ok ', ' ok '];
    }

    /**
     * @dataProvider successIdsDataProvider
     */
    public function testSuccess(string $id, string $expect): void
    {
        $asClosureAttr = new ProxyClosure($id);

        $this->assertEquals($expect, $asClosureAttr->getIdentifier());
    }

    public function failIdsDataProvider(): \Generator
    {
        yield 'empty string' => [''];

        yield 'empty spaces' => ['  '];
    }

    /**
     * @dataProvider failIdsDataProvider
     */
    public function testFailure(string $id): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessageMatches('/Attribute .+ProxyClosure.+ must has the parameter \$id a non-empty string/');

        new ProxyClosure($id);
    }
}
