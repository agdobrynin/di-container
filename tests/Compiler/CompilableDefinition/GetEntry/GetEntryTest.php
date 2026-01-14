<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\GetEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionException;
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
}
