<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\CallableParser\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Traits\CallableParserTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class CallableParserTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use CallableParserTrait;
    use PsrContainerTrait; // 🧨 need for abstract method getContainer in CallableParserTrait.

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
        $mockContainer = $this->createMock(ContainerInterface::class);
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
        $mockContainer = $this->createMock(ContainerInterface::class);
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
        $mockContainer = $this->createMock(ContainerInterface::class);
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
}
