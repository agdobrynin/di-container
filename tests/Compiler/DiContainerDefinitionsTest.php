<?php

declare(strict_types=1);

namespace Tests\Compiler;

use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\IdsIteratorInterface;
use Kaspi\DiContainer\Interfaces\DiContainerGetterDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 */
#[CoversClass(DiContainerDefinitions::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(IdsIterator::class)]
#[CoversClass(NotFoundException::class)]
class DiContainerDefinitionsTest extends TestCase
{
    private DiContainerGetterDefinitionInterface&DiContainerInterface $containerMock;
    private IdsIteratorInterface $idsIterator;

    public function setUp(): void
    {
        $this->containerMock = $this->createMockForIntersectionOfInterfaces([
            DiContainerGetterDefinitionInterface::class,
            DiContainerInterface::class,
        ]);
        $this->idsIterator = $this->createMock(IdsIteratorInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->containerMock, $this->idsIterator);
    }

    public function testGetContainerAndDefaultSingleton(): void
    {
        $this->containerMock->method('getConfig')
            ->willReturn(
                new DiContainerConfig(isSingletonServiceDefault: true),
            )
        ;

        $d = new DiContainerDefinitions($this->containerMock, $this->idsIterator);

        self::assertInstanceOf(DiContainerInterface::class, $d->getContainer());
        self::assertInstanceOf(DiContainerGetterDefinitionInterface::class, $d->getContainer());
        self::assertTrue($d->isSingletonDefinitionDefault());
    }

    public function testExcludeContainerIdentifierFromContainer(): void
    {
        $this->containerMock->method('getDefinitions')
            ->willReturnCallback(
                static function () {
                    yield 'foo' => 'ok foo';

                    yield 'bar' => 'ok bar';
                }
            )
        ;

        $d = new DiContainerDefinitions($this->containerMock, $this->idsIterator);
        $d->excludeContainerIdentifier('foo');

        self::assertEquals(['bar' => 'ok bar'], [...$d->getDefinitions()]);
    }

    public function testExcludeContainerIdentifierFromIdsIterator(): void
    {
        $d = new DiContainerDefinitions($this->containerMock, new IdsIterator());
        $d->excludeContainerIdentifier('foo');

        $d->pushToDefinitionIterator('foo');
        $d->pushToDefinitionIterator('bar');

        self::assertTrue($d->getDefinitions()->valid());
        self::assertEquals('bar', $d->getDefinitions()->key());

        $d->getDefinitions()->next();

        self::assertFalse($d->getDefinitions()->valid());
    }

    public function testReset(): void
    {
        $this->containerMock->method('getDefinitions')
            ->willReturnCallback(static function () {
                yield 'foo' => 'ok foo';

                yield 'bar' => 'ok bar';
            })
        ;

        $d = new DiContainerDefinitions($this->containerMock, new IdsIterator());
        $d->excludeContainerIdentifier('foo', 'baz');

        $d->pushToDefinitionIterator('baz');
        $d->pushToDefinitionIterator('qux');
        $d->pushToDefinitionIterator('quux');

        self::assertCount(3, [...$d->getDefinitions()]);

        $d->reset();

        self::assertCount(2, [...$d->getDefinitions()]);
    }

    public function testFailGetDefinitionFromContainer(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get definition via container identifier "foo"');

        $this->containerMock->method('getDefinition')
            ->with('foo')
            ->willThrowException(new ContainerException())
        ;

        $this->idsIterator->method('current')
            ->willReturn('foo')
        ;

        (new DiContainerDefinitions($this->containerMock, $this->idsIterator))
            ->getDefinitions()
            ->valid()
        ;
    }

    public function testFallbackGetDefinitionFromContainer(): void
    {
        $this->containerMock->method('getDefinition')
            ->with('foo')
            ->willThrowException(new NotFoundException('foo'))
        ;

        $this->idsIterator->method('current')
            ->willReturn('foo')
        ;

        $fallbackEntry = (new DiContainerDefinitions($this->containerMock, $this->idsIterator))
            ->getDefinitions(static fn ($id, $e) => (object) ['id' => $id, 'e' => $e])
            ->current()
        ;

        self::assertEquals('foo', $fallbackEntry->id);
        self::assertInstanceOf(NotFoundExceptionInterface::class, $fallbackEntry->e);
    }

    public function testFallbackGetDefinitionFromContainerWithAutowireException(): void
    {
        $this->containerMock->method('getDefinition')
            ->with('foo')
            ->willThrowException(new AutowireException()) // some exception in DiContainer::resolveDefinition()
        ;

        $this->idsIterator->method('current')
            ->willReturn('foo')
        ;

        $fallbackEntry = (new DiContainerDefinitions($this->containerMock, $this->idsIterator))
            ->getDefinitions(static fn ($id, $e) => (object) ['id' => $id, 'e' => $e])
            ->current()
        ;
        self::assertEquals('foo', $fallbackEntry->id);
        self::assertInstanceOf(DefinitionCompileExceptionInterface::class, $fallbackEntry->e);
    }
}
