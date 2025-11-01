<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Raw\Fixtures\MyDiFactory;

/**
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 *
 * @internal
 */
class InjectByCallableTest extends TestCase
{
    /**
     * @dataProvider successIdsDataProvider
     */
    public function testSuccess(string $id, string $expectIdentifier): void
    {
        $attr = new InjectByCallable($id);

        $this->assertEquals($expectIdentifier, $attr->getIdentifier());
    }

    public function successIdsDataProvider(): Generator
    {
        yield 'string' => ['ok', 'ok'];

        yield 'string invoke method' => [MyDiFactory::class, 'Tests\Attributes\Raw\Fixtures\MyDiFactory'];
    }

    /**
     * @dataProvider failIdsDataProvider
     */
    public function testFailure(string $id): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('The $callable parameter must be a non-empty string and must not contain spaces');

        new InjectByCallable($id);
    }

    public function failIdsDataProvider(): Generator
    {
        yield 'empty string' => [''];

        yield 'empty spaces' => ['  '];

        yield 'string with spaces in middle' => ['ok  yes'];

        yield 'string with space trailing' => [' yes'];

        yield 'string with space ending' => ['yes '];
    }
}
