<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionProxyClosure;

use Closure;
use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\ContainerNeedSetException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 *
 * @internal
 */
class MainTest extends TestCase
{
    public function successDefinitionDataProvider(): Generator
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
        $this->assertEquals($expect, (new DiDefinitionProxyClosure($id))->getDefinition());
    }

    public function failDefinitionDataProvider(): Generator
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

        (new DiDefinitionProxyClosure($id))->getDefinition();
    }

    public function testContainerNeedSet(): void
    {
        $this->expectException(ContainerNeedSetException::class);
        $this->expectExceptionMessage('Use method setContainer() in "Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure" class.');

        (new DiDefinitionProxyClosure('ok'))->invoke();
    }

    public function testContainerDefinitionHasNot(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(false)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Definition "ok" does not exist');

        (new DiDefinitionProxyClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;
    }

    public function testContainerDefinitionHas(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(true)
        ;

        $res = (new DiDefinitionProxyClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;

        $this->assertInstanceOf(Closure::class, $res);
    }

    public function testContainerDefinitionHasAndResolveSuccess(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())->method('has')
            ->with('ok')
            ->willReturn(true)
        ;
        $mockContainer->expects(self::once())->method('get')
            ->with('ok')
            ->willReturn('result of get')
        ;

        $res = (new DiDefinitionProxyClosure('ok'))
            ->setContainer($mockContainer)
            ->invoke()
        ;

        $this->assertEquals('result of get', $res());
    }
}
