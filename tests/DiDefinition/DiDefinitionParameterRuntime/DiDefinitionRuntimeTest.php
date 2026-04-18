<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionParameterRuntime;

use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterRuntime;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionParameterRuntime::class)]
class DiDefinitionRuntimeTest extends TestCase
{
    private SourceParametersMutableInterface $sourceParams;
    private DiContainerInterface $container;

    protected function setUp(): void
    {
        $this->sourceParams = $this->createMock(SourceParametersMutableInterface::class);
        $this->container = $this->createMock(DiContainerInterface::class);
        $this->container->method('parameters')->willReturn($this->sourceParams);
    }

    protected function tearDown(): void
    {
        unset($this->sourceParams, $this->container);
    }

    public function testParameterNameNotDefined(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Parameter name must be non-empty string');

        $this->sourceParams->expects(self::never())
            ->method('has')
        ;

        $p = new DiDefinitionParameterRuntime();
        $p->resolve($this->container);
    }

    public function testResolveWithName(): void
    {
        $paramName = 'foo';

        $this->sourceParams->expects(self::once())
            ->method('has')
            ->with($paramName)
            ->willReturn(true)
        ;
        $this->sourceParams->expects(self::once())
            ->method('get')
            ->with($paramName)
            ->willReturn('bar')
        ;

        $p = new DiDefinitionParameterRuntime($paramName);

        self::assertEquals('bar', $p->resolve($this->container));
        self::assertEquals($paramName, $p->getDefinition());
        self::assertNull($p->getContext());
    }

    public function testResolveWithSetContext(): void
    {
        $paramName = 'foo';

        $this->sourceParams->expects(self::once())
            ->method('has')
            ->with($paramName)
            ->willReturn(true)
        ;
        $this->sourceParams->expects(self::once())
            ->method('get')
            ->with($paramName)
            ->willReturn('bar')
        ;

        $p = (new DiDefinitionParameterRuntime())
            ->setContext($paramName)
        ;

        self::assertEquals('bar', $p->resolve($this->container));
        self::assertEquals($paramName, $p->getContext());
        self::assertEquals('', $p->getDefinition());
    }

    public function testResolveWithContext(): void
    {
        $paramName = 'foo';

        $this->sourceParams->expects(self::once())
            ->method('has')
            ->with($paramName)
            ->willReturn(true)
        ;
        $this->sourceParams->expects(self::once())
            ->method('get')
            ->with($paramName)
            ->willReturn('bar')
        ;

        $p = new DiDefinitionParameterRuntime();

        self::assertEquals('bar', $p->resolve($this->container, $paramName));
        self::assertNull($p->getContext());
        self::assertEquals('', $p->getDefinition());
    }

    public function testResolveWhenParameterNotDefined(): void
    {
        $paramName = 'foo';
        $message = 'You are forget to define parameter "foo"';

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage($message);

        $this->sourceParams->expects(self::never())
            ->method('get')
        ;

        $this->sourceParams->expects(self::once())
            ->method('has')
            ->with($paramName)
            ->willReturn(false)
        ;

        $p = new DiDefinitionParameterRuntime($paramName, $message);

        self::assertEquals($message, $p->getMessage());

        $p->resolve($this->container);
    }
}
