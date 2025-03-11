<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser;

use Generator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\CallableParser\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Traits\CallableParserTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class CallableParserTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use CallableParserTrait;
    use DiContainerTrait; // 🧨 need for abstract method getContainer in CallableParserTrait.

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

    public function testDefinitionAsClassWithMethodAsArray(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('has')->with(SuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willReturn(new SuperClass('srv'))
        ;
        $this->setContainer($mockContainer);

        $definition = [SuperClass::class, 'method'];
        $parsedDefinition = $this->parseCallable($definition);

        $this->assertIsCallable($parsedDefinition);
        $this->assertIsNotCallable($definition);
    }

    public function testDefinitionAsClassWithMethodAsString(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('has')->with(SuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willReturn(new SuperClass('srv'))
        ;
        $this->setContainer($mockContainer);

        $definition = SuperClass::class.'::method';
        $parsedDefinition = $this->parseCallable($definition);

        $this->assertIsCallable($parsedDefinition);
        $this->assertIsNotCallable($definition);
    }

    public function testDefinitionAsClassAsStringAndHiddenInvokeMethod(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('has')->with(SuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willReturn(new SuperClass('srv'))
        ;
        $this->setContainer($mockContainer);

        $definition = SuperClass::class;
        $parsedDefinition = $this->parseCallable($definition);

        $this->assertIsCallable($parsedDefinition);
        $this->assertIsNotCallable($definition);
    }

    public static function dataProviderDefinitionAsStringWithDoubleColonNotValid(): Generator
    {
        yield 'class and method is empty' => ['::'];

        yield 'class is empty' => ['::method'];

        yield 'method is empty' => ['class::'];
    }

    /**
     * @dataProvider dataProviderDefinitionAsStringWithDoubleColonNotValid
     */
    public function testParseDefinitionAsStringWithDoubleColonNotValid(string $definition): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Wrong callable definition present. Got: '.$definition);

        $this->parseDefinitions($definition);
    }
}
