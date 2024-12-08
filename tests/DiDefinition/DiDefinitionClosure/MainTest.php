<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionClosure;

use Kaspi\DiContainer\DiDefinition\DiDefinitionClosure;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\ContainerNeedSetException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionClosure
 *
 * @internal
 */
class MainTest extends TestCase
{
    public function successDefinitionDataProvider(): \Generator
    {
        yield 'string' => ['ok', 'ok'];

        yield 'string with space' => [' ok', ' ok'];

        yield 'string with spaces' => [' ok ', ' ok '];
    }

    /**
     * @dataProvider successDefinitionDataProvider
     */
    public function testDefinitionSuccess(string $id, string $expect): void
    {
        $this->assertEquals($expect, (new DiDefinitionClosure($id))->getDefinition());
    }

    public function failDefinitionDataProvider(): \Generator
    {
        yield 'empty string' => [''];

        yield 'string with space' => [' '];

        yield 'string with spaces' => ['   '];
    }

    /**
     * @dataProvider failDefinitionDataProvider
     */
    public function testDefinitionFail(string $id): void
    {
        $this->expectException(AutowireException::class);
        $this->expectExceptionMessage('must be non-empty string');

        (new DiDefinitionClosure($id))->getDefinition();
    }

    public function testContainerNeedSet(): void
    {
        $this->expectException(ContainerNeedSetException::class);
        $this->expectExceptionMessage('Use method setContainer() in Kaspi\DiContainer\DiDefinition\DiDefinitionClosure class.');

        (new DiDefinitionClosure('ok'))->invoke();
    }

    public function testContainerDefinitionHasNot(): void
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(false)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Definition "ok" does not exist');

        (new DiDefinitionClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;
    }

    public function testContainerDefinitionHas(): void
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(true)
        ;

        $res = (new DiDefinitionClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;

        $this->assertInstanceOf(\Closure::class, $res);
    }

    public function testContainerDefinitionHasAndResolveSuccess(): void
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(true)
        ;
        $mockContainer->expects(self::once())->method('get')
            ->with('ok')
            ->willReturn('result of get')
        ;

        $res = (new DiDefinitionClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;

        $this->assertEquals('result of get', $res());
    }
}
