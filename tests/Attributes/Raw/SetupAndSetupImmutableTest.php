<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\Attributes\Setup
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\diGet
 *
 * @internal
 */
class SetupAndSetupImmutableTest extends TestCase
{
    public function testFailSetupNotSetMethod(): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        (new Setup())->getIdentifier();
    }

    public function testFailSetupImmutableNotSetMethod(): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        (new SetupImmutable())->getIdentifier();
    }

    public function dataProviderMethod(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];

        yield 'not valid string "11111"' => ['11111'];

        yield 'not valid string "1method"' => ['1method'];

        yield 'not valid string "method method"' => ['method method'];

        yield 'not valid string " method"' => [' method'];
    }

    /**
     * @dataProvider dataProviderMethod
     */
    public function testFailSetupSetMethod(string $method): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        $s = new Setup();
        $s->setMethod($method);
    }

    /**
     * @dataProvider dataProviderMethod
     */
    public function testFailSetupImmutableSetMethod(string $method): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        $s = new SetupImmutable();
        $s->setMethod($method);
    }

    public function dataProviderSuccessMethod(): Generator
    {
        yield 'success name #1' => ['withLogger'];

        yield 'success name #2' => ['setLogger'];

        yield 'success name #3' => ['set2'];

        yield 'success name #4' => ['with1'];

        yield 'success name #5' => ['a'];
    }

    /**
     * @dataProvider dataProviderSuccessMethod
     */
    public function testSuccessSetupSetMethod(string $method): void
    {
        $s = new Setup();
        $s->setMethod($method);

        self::assertEquals($method, $s->getIdentifier());
        self::assertEquals([], $s->getArguments());
    }

    /**
     * @dataProvider dataProviderSuccessMethod
     */
    public function testSuccessSetupImmutableSetMethod(string $method): void
    {
        $s = new SetupImmutable();
        $s->setMethod($method);

        self::assertEquals($method, $s->getIdentifier());
        self::assertEquals([], $s->getArguments());
    }

    public function testSetupNamedArgument(): void
    {
        $s = new Setup(one: 'first', two: 'second');

        self::assertEquals(['one' => 'first', 'two' => 'second'], $s->getArguments());
    }

    public function testSetupMixedNamedArgument(): void
    {
        $s = new Setup('first', two: 'second');

        self::assertEquals([0 => 'first', 'two' => 'second'], $s->getArguments());
    }

    public function testSetupMixedNamedArgumentWithValueAsObject(): void
    {
        $s = new Setup('first', two: diGet('service.one'));

        self::assertEquals([0 => 'first', 'two' => diGet('service.one')], $s->getArguments());
    }

    public function testSetupImmutableNamedArgument(): void
    {
        $s = new SetupImmutable(...['one' => 'first', 'two' => 'second']);

        self::assertEquals(['one' => 'first', 'two' => 'second'], $s->getArguments());
    }

    public function testSetupImmutableMixedNamedArgument(): void
    {
        $s = new SetupImmutable(...['first', 'two' => 'second']);

        self::assertEquals([0 => 'first', 'two' => 'second'], $s->getArguments());
    }

    public function testSetupImmutableNamedArgumentWithValueAsObject(): void
    {
        $s = new SetupImmutable(...['one' => diGet('service.a'), 'two' => diAutowire('MyClass')]);

        self::assertEquals(['one' => diGet('service.a'), 'two' => diAutowire('MyClass')], $s->getArguments());
    }

    public function testSetupImmutableMixedNamedArgumentWithValueAsObject(): void
    {
        $s = new SetupImmutable(...['first', 'two' => diAutowire('App\MyClass')]);

        self::assertEquals([0 => 'first', 'two' => diAutowire('App\MyClass')], $s->getArguments());
    }
}
