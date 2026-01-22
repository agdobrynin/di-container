<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\FactoryEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\FactoryEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\ArgumentBuilderException;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures\FooFactory;

/**
 * @internal
 */
#[CoversClass(FactoryEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntry::class)]
class FactoryEntryTest extends TestCase
{
    private DiDefinitionFactoryInterface $mockDefinition;
    private DiContainerDefinitionsInterface $mockDiContainerDefinitions;
    private DiDefinitionTransformerInterface $mockTransformer;

    public function setUp(): void
    {
        $this->mockDefinition = $this->createMock(DiDefinitionFactoryInterface::class);
        $this->mockDiContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->mockTransformer = $this->createMock(DiDefinitionTransformerInterface::class);
    }

    public function tearDown(): void
    {
        unset($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer);
    }

    public function testGetDefinition(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(FooFactory::class)
        ;

        $ce = new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer);

        self::assertEquals(FooFactory::class, $ce->getDiDefinition()->getDefinition());
    }

    public function testCannotTransformFactoryClass(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile factory class');

        $this->mockTransformer->method('transform')
            ->willThrowException(new DefinitionCompileException())
        ;

        (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCannotCompileFactoryClass(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile factory class');

        $mockObjectEntry = $this->createMock(CompilableDefinitionInterface::class);
        $mockObjectEntry->method('compile')
            ->willThrowException(new DefinitionCompileException())
        ;

        $this->mockTransformer->method('transform')
            ->willReturn($mockObjectEntry)
        ;

        (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCannotExposeFactoryMethod(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot provide arguments to a factory method in definition Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures\FooFactory::__invoke()');

        $this->mockDefinition->method('getDefinition')
            ->willReturn(FooFactory::class)
        ;
        $this->mockDefinition->method('getFactoryMethod')
            ->willReturn('__invoke')
        ;

        $this->mockDefinition->method('exposeFactoryMethodArgumentBuilder')
            ->willThrowException(new DiDefinitionException())
        ;

        (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCannotBuildArgumentsToFactoryMethod(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build arguments for factory method Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures\FooFactory::__invoke()');

        $mockArgBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(FooFactory::class, '__invoke'))
        ;

        $mockArgBuilder->method('build')
            ->willThrowException(new ArgumentBuilderException())
        ;

        $this->mockDefinition->method('exposeFactoryMethodArgumentBuilder')
            ->willReturn($mockArgBuilder)
        ;

        (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCannotCompileFactoryMethodArguments(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile arguments for factory method Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures\FooFactory::__invoke()');

        $mockArgBuilder = $this->createMock(ArgumentBuilderInterface::class);

        $mockArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(FooFactory::class, '__invoke'))
        ;

        $mockArgBuilder->method('build')
            ->willReturn([
                new stdClass(),
            ])
        ;

        $this->mockDefinition->method('exposeFactoryMethodArgumentBuilder')
            ->willReturn($mockArgBuilder)
        ;

        $this->mockTransformer
            ->expects(self::exactly(2))
            ->method('transform')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(CompilableDefinitionInterface::class),
                self::throwException(new DefinitionCompileException())
            )
        ;

        (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCompileFactory(): void
    {
        $this->mockDiContainerDefinitions->method('isSingletonDefinitionDefault')
            ->willReturn(true)
        ;

        $mockArgBuilder = $this->createMock(ArgumentBuilderInterface::class);

        $mockArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(FooFactory::class, '__invoke'))
        ;

        $mockArgBuilder->method('build')
            ->willReturn([
                new DiDefinitionGet(ContainerInterface::class),
            ])
        ;

        $this->mockDefinition->method('exposeFactoryMethodArgumentBuilder')
            ->willReturn($mockArgBuilder)
        ;

        $mockAutowire = $this->createMock(DiDefinitionAutowireInterface::class);
        $mockAutowire->method('getDefinition')
            ->willReturn(new ReflectionClass(FooFactory::class))
        ;

        $mockLink = $this->createMock(DiDefinitionLinkInterface::class);
        $mockLink->method('getDefinition')
            ->willReturn(ContainerInterface::class)
        ;

        $this->mockTransformer
            ->expects(self::exactly(2))
            ->method('transform')
            ->willReturnOnConsecutiveCalls(
                new ObjectEntry(
                    $mockAutowire,
                    $this->mockDiContainerDefinitions,
                    $this->mockTransformer
                ),
                new GetEntry(
                    $mockLink,
                    $this->mockDiContainerDefinitions,
                ),
            )
        ;

        $compiledFactory = (new FactoryEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;

        self::assertEquals('mixed', $compiledFactory->getReturnType());

        self::assertEquals(
            [
                0 => '$object = new \Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures\FooFactory',
            ],
            $compiledFactory->getStatements()
        );

        self::assertEquals(
            '$object->(
  $this->get(\'Psr\\\Container\\\ContainerInterface\'),
)',
            $compiledFactory->getExpression()
        );

        self::assertTrue($compiledFactory->isSingleton());
        self::assertEquals('$object', $compiledFactory->getScopeServiceVar());
        self::assertEquals(['$this', '$object'], $compiledFactory->getScopeVars());
    }
}
