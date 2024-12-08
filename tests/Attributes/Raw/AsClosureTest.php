<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\AsClosure;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\AsClosure
 *
 * @internal
 */
class AsClosureTest extends TestCase
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
    public function testAsClosureSuccess(string $id, string $expect): void
    {
        $asClosureAttr = new AsClosure($id);

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
    public function testAsClosureFailure(string $id): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('must be a non-empty string');

        new AsClosure($id);
    }
}
