<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ProxyClosureEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ProxyClosureEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Compiler\CompilableDefinition\ProxyClosureEntry\Fixtures\HeavyFoo;

/**
 * @internal
 */
#[CoversClass(ProxyClosureEntry::class)]
#[CoversClass(CompiledEntry::class)]
class ProxyClosureEntryTest extends TestCase
{
    private DiContainerDefinitionsInterface $mockContainerDefinitions;
    private DiDefinitionProxyClosureInterface $mockDefinition;

    public function setUp(): void
    {
        $this->mockContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->mockDefinition = $this->createMock(DiDefinitionProxyClosureInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->mockDefinition, $this->mockContainerDefinitions);
    }

    public function testGetDefinition(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(HeavyFoo::class)
        ;

        $ce = new ProxyClosureEntry($this->mockDefinition, $this->mockContainerDefinitions);

        self::assertEquals(HeavyFoo::class, $ce->getDiDefinition()->getDefinition());
    }

    public function testFailCompile(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);

        $this->mockDefinition->method('getDefinition')
            ->willThrowException(new DiDefinitionException())
        ;

        (new ProxyClosureEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;
    }

    public function testSuccessCompile(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(HeavyFoo::class)
        ;

        $this->mockContainerDefinitions->method('isSingletonDefinitionDefault')
            ->willReturn(true)
        ;

        $this->mockContainerDefinitions->expects(self::once())
            ->method('pushToDefinitionIterator')
            ->with(HeavyFoo::class)
        ;

        $ce = (new ProxyClosureEntry($this->mockDefinition, $this->mockContainerDefinitions))
            ->compile('$this')
        ;

        self::assertEquals('\Closure', $ce->getReturnType());
        self::assertEquals(true, $ce->isSingleton());
        self::assertEquals('fn () => $this->get(\'Tests\\\Compiler\\\CompilableDefinition\\\ProxyClosureEntry\\\Fixtures\\\HeavyFoo\')', $ce->getExpression());
    }
}
