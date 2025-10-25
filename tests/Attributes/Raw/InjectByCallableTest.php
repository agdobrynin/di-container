<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use PHPUnit\Framework\TestCase;

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
    public function testSuccess(string $id, ?bool $isSingleton, string $expectIdentifier, ?bool $expectIsSingleton): void
    {
        $attr = null === $isSingleton
            ? new InjectByCallable($id)
            : new InjectByCallable($id, $isSingleton);

        $this->assertEquals($expectIdentifier, $attr->getIdentifier());
        $this->assertEquals($expectIsSingleton, $attr->isSingleton());
    }

    public function successIdsDataProvider(): Generator
    {
        yield 'string' => ['ok', null, 'ok', null];

        yield 'string with singleton false' => ['ok', false, 'ok', false];

        yield 'string aka static method' => ['MyClass::ok', true, 'MyClass::ok', true];
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
