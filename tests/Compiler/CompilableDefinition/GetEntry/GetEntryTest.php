<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\GetEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Compiler\CompilableDefinition\GetEntry\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(CompiledEntry::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
class GetEntryTest extends TestCase
{
    private DiContainerDefinitionsInterface $mockContainerDefinitions;
    private DiDefinitionLinkInterface $mockDefinition;

    public function setUp(): void
    {
        $this->mockContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->mockDefinition = $this->createMock(DiDefinitionLinkInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->mockDefinition, $this->mockContainerDefinitions);
    }

    public function testGetDefinition(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn('foo')
        ;

        $ce = new GetEntry($this->mockDefinition, $this->mockContainerDefinitions);

        self::assertEquals('foo', $ce->getDiDefinition()->getDefinition());
    }

    public function testFailCompile(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);

        $this->mockDefinition->method('getDefinition')
            ->willThrowException(new DiDefinitionException())
        ;

        (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;
    }

    public function testSuccessCompile(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(Foo::class)
        ;

        $this->mockContainerDefinitions->method('isSingletonDefinitionDefault')
            ->willReturn(true)
        ;

        $this->mockContainerDefinitions->expects(self::once())
            ->method('pushToDefinitionIterator')
            ->with('Tests\Compiler\CompilableDefinition\GetEntry\Fixtures\Foo')
        ;

        $ce = (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;

        self::assertEquals('mixed', $ce->getReturnType());
        // GetEntry always isSingleton = false!
        self::assertEquals(false, $ce->isSingleton());
        self::assertEquals('$this->get(\'Tests\\\Compiler\\\CompilableDefinition\\\GetEntry\\\Fixtures\\\Foo\')', $ce->getExpression());
    }

    public function testReferenceToReferenceWithException(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Get reference from "foo"');

        $this->mockDefinition->method('getDefinition')
            ->willReturn('foo')
        ;
        $this->mockContainerDefinitions->method('getDefinition')
            ->with('foo')
            ->willReturn(new DiDefinitionGet(''))
        ;

        (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;
    }

    public function testReferenceToReferenceCircular(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Detected circular call reference for container identifiers "foo" -> "baz" -> "foo"');

        $this->mockDefinition->method('getDefinition')
            ->willReturn('foo')
        ;
        $this->mockContainerDefinitions->method('getDefinition')
            ->willReturnMap([
                ['foo', null, new DiDefinitionGet('baz')],
                ['baz', null, new DiDefinitionGet('foo')],
            ])
        ;

        (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;
    }

    public function testReferenceToReferenceSuccess(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn('foo')
        ;
        $this->mockContainerDefinitions->method('getDefinition')
            ->willReturnMap([
                ['foo', null, new DiDefinitionGet('baz')],
                ['baz', null, new DiDefinitionGet('qux')],
                ['qux', null, new DiDefinitionValue('success')],
            ])
        ;

        $ce = (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;

        self::assertEquals('$this->get(\'qux\')', $ce->getExpression());
    }

    public function testSkipCompileExcludedIds(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn('foo')
        ;
        $this->mockContainerDefinitions->method('isContainerIdentifierExcluded')
            ->with('foo')
            ->willReturn(true)
        ;

        $ce = (new GetEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;

        self::assertEquals('$this->get(\'foo\')', $ce->getExpression());
    }
}
