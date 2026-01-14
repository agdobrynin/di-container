<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\TaggedAsEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\TaggedAsEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TaggedAsEntry::class)]
#[CoversClass(CompiledEntry::class)]
class TaggedAsEntryTest extends TestCase
{
    private DiDefinitionTaggedAsInterface $mockDefinition;
    private DiContainerDefinitionsInterface $mockContainer;

    public function setUp(): void
    {
        $this->mockContainer = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->mockDefinition = $this->createMock(DiDefinitionTaggedAsInterface::class);
        $this->mockDefinition->method('getDefinition')
            ->willReturn('tags.foo')
        ;
    }

    public function tearDown(): void
    {
        unset($this->mockDefinition, $this->mockContainer);
    }

    public function testGetDefinition(): void
    {
        $te = new TaggedAsEntry($this->mockDefinition, $this->mockContainer);

        self::assertInstanceOf(DiDefinitionTaggedAsInterface::class, $te->getDiDefinition());
    }

    public function testFailCompileDefinitionCannotProvideIds(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot provide container identifiers for tag "tags.foo"');

        $this->mockDefinition->method('exposeContainerIdentifiers')
            ->willThrowException(new DiDefinitionException())
        ;

        (new TaggedAsEntry($this->mockDefinition, $this->mockContainer))
            ->compile('$this')
        ;
    }

    public function testCompileNotLazy(): void
    {
        $this->mockDefinition->method('exposeContainerIdentifiers')
            ->willReturn([
                'services.foo',
                'services.bar',
            ])
        ;
        $this->mockDefinition->method('isLazy')
            ->willReturn(false)
        ;

        $ct = (new TaggedAsEntry($this->mockDefinition, $this->mockContainer))
            ->compile('$this')
        ;

        self::assertEquals(['$object'], $ct->getScopeVars());
        self::assertEquals('$object', $ct->getScopeServiceVar());
        self::assertEquals([], $ct->getStatements());
        self::assertEquals('array', $ct->getReturnType());
        self::assertEquals(
            '/* Services for tag \'tags.foo\' */
[
  0 => $this->get(\'services.foo\'),
  1 => $this->get(\'services.bar\'),
]',
            $ct->getExpression()
        );
    }

    public function testCompileLazy(): void
    {
        $this->mockDefinition->method('exposeContainerIdentifiers')
            ->willReturn([
                'services.foo',
                'services.bar',
            ])
        ;
        $this->mockDefinition->method('isLazy')
            ->willReturn(true)
        ;

        $ct = (new TaggedAsEntry($this->mockDefinition, $this->mockContainer))
            ->compile('$this')
        ;

        self::assertEquals(['$object'], $ct->getScopeVars());
        self::assertEquals('$object', $ct->getScopeServiceVar());
        self::assertEquals([], $ct->getStatements());
        self::assertEquals('\Kaspi\DiContainer\LazyDefinitionIterator', $ct->getReturnType());
        self::assertEquals(
            '/* Lazy load services for tag \'tags.foo\' */
new \Kaspi\DiContainer\LazyDefinitionIterator($this, [
  0 => \'services.foo\',
  1 => \'services.bar\',
])',
            $ct->getExpression()
        );
    }
}
