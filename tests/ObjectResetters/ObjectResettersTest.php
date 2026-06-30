<?php

declare(strict_types=1);

namespace Tests\ObjectResetters;

use Closure;
use Generator;
use Kaspi\DiContainer\Interfaces\Exceptions\ResetterExceptionInterface;
use Kaspi\DiContainer\ObjectResetters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * @internal
 */
#[CoversClass(ObjectResetters::class)]
class ObjectResettersTest extends TestCase
{
    #[TestWith([
        [0 => 'foo'],
        'The iterator key must be a non-empty string.',
    ])]
    #[TestWith([
        ['' => 'foo'],
        'The iterator key must be a non-empty string.',
    ])]
    #[TestWith([
        ['foo' => new stdClass()],
        'Resetter with the key \'foo\' must be is',
    ])]
    #[TestWith([
        ['foo' => true],
        'Resetter with the key \'foo\' must be is',
    ])]
    public function testObjectResettersSetupException(iterable $setupResetters, string $expectExceptionMessage): void
    {
        $this->expectException(ResetterExceptionInterface::class);
        $this->expectExceptionMessage($expectExceptionMessage);

        (new ObjectResetters($this->createMock(ContainerInterface::class)))
            ->setup($setupResetters)
        ;
    }

    public function testObjectResettersGetConfiguresResetters(): void
    {
        $setupResetters = static function () {
            yield 'foo' => 'foo';

            yield 'bar' => static function ($object): void {
                $object->foo = [];
            };

            yield Fixtures\Foo::class => [Fixtures\FooResetter::class, 'doReset'];
        };

        $objectResetters = new ObjectResetters($this->createMock(ContainerInterface::class));
        $objectResetters->setup($setupResetters());

        $resetters = [...$objectResetters->resetters()];

        self::assertCount(3, $resetters);
        self::assertEquals('foo', $resetters['foo']);
        self::assertInstanceOf(Closure::class, $resetters['bar']);
        self::assertEquals([Fixtures\FooResetter::class, 'doReset'], $resetters[Fixtures\Foo::class]);

        // Deletes previous resetters and creates a new one when call `setup()` again.
        $objectResetters->setup([]);
        self::assertCount(0, [...$objectResetters->resetters()]);
    }

    #[DataProvider('dataProviderResetServiceException')]
    public function testResetServiceException($id, $containerGotResult, iterable $setup, string $expectExceptionMessage): void
    {
        $this->expectException(ResetterExceptionInterface::class);
        $this->expectExceptionMessageMatches($expectExceptionMessage);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->with($id)
            ->willReturn($containerGotResult)
        ;

        $objectResetters = new ObjectResetters($containerMock);
        $objectResetters->setup($setup);
        $objectResetters->reset();
    }

    public static function dataProviderResetServiceException(): Generator
    {
        yield 'service is non-object' => [
            'id' => 'foo',
            'containerGotResult' => 'str',
            'setup' => ['foo' => 'doReset'],
            'expectExceptionMessage' => '/Entry with container identifier \'foo\' should return type "object"/',
        ];

        yield 'service has not public reset method' => [
            'id' => stdClass::class,
            'containerGotResult' => new stdClass(),
            'setup' => [stdClass::class => 'doReset'],
            'expectExceptionMessage' => '/^Resetter must be is.*existing public method in class "stdClass" class.+Got: \'doReset\' as type "string"/',
        ];
    }

    #[DataProvider('dataProviderResetService')]
    public function testResetService(callable|string $resetter, string $expectFlushesValue): void
    {
        $foo = new Fixtures\Foo();
        $foo->foo = 'init long string';

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->with(Fixtures\Foo::class)
            ->willReturn($foo)
        ;

        $objectResetters = new ObjectResetters($containerMock);
        $objectResetters->setup([Fixtures\Foo::class => $resetter]);
        $objectResetters->reset();

        self::assertEquals($expectFlushesValue, $foo->foo);
    }

    public static function dataProviderResetService(): Generator
    {
        yield 'public static method in class' => [
            'resetter' => [Fixtures\FooResetter::class, 'doReset'],
            'expectFlushesValue' => 'foo',
        ];

        yield 'resetter as function' => [
            'resetter' => '\Tests\ObjectResetters\Fixtures\resetFoo',
            'expectFlushesValue' => 'fn',
        ];

        yield 'resetter as \Closure' => [
            'resetter' => static function (Fixtures\Foo $foo) {
                $foo->foo = 'closure empty';
            },
            'expectFlushesValue' => 'closure empty',
        ];

        yield 'resetter as method name in object class' => [
            'resetter' => 'flush',
            'expectFlushesValue' => 'null',
        ];
    }
}
