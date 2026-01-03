<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\ArgumentBuilderException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Bar;
use Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Baz;
use Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(ObjectEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(\Kaspi\DiContainer\Helper::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
class ObjectEntryTest extends TestCase
{
    private DiDefinitionAutowireInterface $mockDefinition;
    private DiContainerDefinitionsInterface $mockDiContainerDefinitions;
    private DiDefinitionTransformerInterface $mockTransformer;

    public function setUp(): void
    {
        $this->mockDefinition = $this->createMock(DiDefinitionAutowireInterface::class);
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
            ->willReturn(new ReflectionClass(Foo::class))
        ;

        $ce = new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer);

        self::assertEquals(Foo::class, $ce->getDiDefinition()->getDefinition()->getName());
    }

    public function testFailConstructorArgBuilder(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot provide constructor arguments to a object definition');

        $this->mockDefinition->method('getIdentifier')
            ->willReturn(Foo::class)
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willThrowException(new DiDefinitionException())
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testFailSetupArgBuilder(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot provide setter method arguments to a object definition');

        $mockConstructorArgBuilder = $this->createMock(ArgumentBuilderInterface::class);

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockConstructorArgBuilder)
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willThrowException(new DiDefinitionException())
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCompileWithoutConstructorAndSetups(): void
    {
        $this->mockDiContainerDefinitions->method('isSingletonDefinitionDefault')
            ->willReturn(true)
        ;

        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Bar::class))
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn(null)
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willReturn([])
        ;

        $compiledObject = (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;

        self::assertEquals('new \Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Bar', $compiledObject->getExpression());
        self::assertEquals([], $compiledObject->getStatements());
        self::assertEquals('\Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Bar', $compiledObject->getReturnType());
        self::assertTrue($compiledObject->isSingleton());
        self::assertEquals('$object', $compiledObject->getScopeServiceVar());
        self::assertEquals(['$this', '$object'], $compiledObject->getScopeVars());
    }

    public function testFailBuildConstructorArgument(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build arguments for constructor class "Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo"');

        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Foo::class))
        ;

        $mockConstructorArgBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockConstructorArgBuilder->method('build')
            ->willThrowException(new ArgumentBuilderException())
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockConstructorArgBuilder)
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testFailCompileConstructorArguments(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile arguments for Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo::__construct()');

        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Foo::class))
        ;

        $mockConstructorArgBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockConstructorArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Foo::class, '__construct'))
        ;

        $mockConstructorArgBuilder->method('build')
            ->willReturn([
                new DiDefinitionGet('services.bar'),
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockConstructorArgBuilder)
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testCompileConstructorArgumentsWithoutSetup(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Foo::class))
        ;

        $mockConstructorArgBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockConstructorArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Foo::class, '__construct'))
        ;

        $mockConstructorArgBuilder->method('build')
            ->willReturn([
                new DiDefinitionGet('services.bar'),
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockConstructorArgBuilder)
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willReturn([])
        ;

        $mockLink = $this->createMock(DiDefinitionLinkInterface::class);
        $mockLink->method('getDefinition')
            ->willReturn('services.bar')
        ;

        // Transform argument
        $this->mockTransformer->method('transform')
            ->willReturn(
                new GetEntry($mockLink, $this->mockDiContainerDefinitions)
            )
        ;

        $compiledObject = (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;

        self::assertEquals('new \Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo(
  $this->get(\'services.bar\'),
)', $compiledObject->getExpression());

        self::assertEquals([], $compiledObject->getStatements());
        self::assertEquals('\Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo', $compiledObject->getReturnType());
        self::assertFalse($compiledObject->isSingleton());
        self::assertEquals('$object', $compiledObject->getScopeServiceVar());
        self::assertEquals(['$this', '$object'], $compiledObject->getScopeVars());
    }

    public function testFailBuildSetupArguments(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build arguments for setter method in definition Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Baz::setContainer()');

        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Baz::class))
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn(null)
        ;

        $mockSetupArgBuilderOne = $this->createMock(ArgumentBuilderInterface::class);
        $mockSetupArgBuilderOne->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Baz::class, 'setContainer'))
        ;

        $mockSetupArgBuilderOne->method('buildByPriorityBindArguments')
            ->willThrowException(new ArgumentBuilderException())
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willReturn([
                0 => [SetupConfigureMethod::Mutable, $mockSetupArgBuilderOne],
            ])
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testFailCompileSetupArguments(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile arguments for Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Baz::setContainer()');

        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Baz::class))
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn(null)
        ;

        $mockSetupArgBuilderOne = $this->createMock(ArgumentBuilderInterface::class);
        $mockSetupArgBuilderOne->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Baz::class, 'setContainer'))
        ;

        $mockSetupArgBuilderOne->method('buildByPriorityBindArguments')
            ->willReturn([
                0 => new DiDefinitionGet('services.internal_container'),
            ])
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willReturn([
                0 => [SetupConfigureMethod::Mutable, $mockSetupArgBuilderOne],
            ])
        ;

        (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->mockTransformer))
            ->compile('$this')
        ;
    }

    public function testSuccessCompileAll(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn(new ReflectionClass(Foo::class))
        ;

        $mockConstructorArgBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockConstructorArgBuilder->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Foo::class, '__construct'))
        ;

        $mockConstructorArgBuilder->method('build')
            ->willReturn([
                new DiDefinitionGet('services.bar'),
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockConstructorArgBuilder)
        ;

        $mockSetupArgBuilderOne = $this->createMock(ArgumentBuilderInterface::class);
        $mockSetupArgBuilderOne->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Foo::class, 'setBaz'))
        ;

        $mockSetupArgBuilderOne->method('buildByPriorityBindArguments')
            ->willReturn([
                0 => new DiDefinitionGet('services.internal_container'),
            ])
        ;

        $mockSetupArgBuilderTwo = $this->createMock(ArgumentBuilderInterface::class);
        $mockSetupArgBuilderTwo->method('getFunctionOrMethod')
            ->willReturn(new ReflectionMethod(Foo::class, 'withContainer'))
        ;

        $mockSetupArgBuilderTwo->method('buildByPriorityBindArguments')
            ->willReturn([
                0 => new DiDefinitionGet(ContainerInterface::class),
            ])
        ;

        $this->mockDefinition->method('exposeSetupArgumentBuilders')
            ->willReturn([
                0 => [SetupConfigureMethod::Mutable, $mockSetupArgBuilderOne],
                1 => [SetupConfigureMethod::Immutable, $mockSetupArgBuilderTwo],
            ])
        ;

        $mockLink = $this->createMock(DiDefinitionLinkInterface::class);
        $mockLink->method('getDefinition')
            ->willReturn('services.bar')
        ;

        // Transform argument
        $transformer = new DiDefinitionTransformer(
            $this->createMock(FinderClosureCodeInterface::class)
        );

        $compiledObject = (new ObjectEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $transformer))
            ->compile('$this')
        ;

        self::assertFalse($compiledObject->isSingleton());
        self::assertEquals('$object', $compiledObject->getExpression());
        self::assertEquals('$object', $compiledObject->getScopeServiceVar());
        self::assertEquals(['$this', '$object'], $compiledObject->getScopeVars());
        self::assertEquals('\Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo', $compiledObject->getReturnType());
        self::assertEquals([
            '$object = new \Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures\Foo(
  $this->get(\'services.bar\'),
)',
            '$object->setBaz(
  $this->get(\'services.internal_container\'),
)',
            '$object = $object->withContainer(
  $this->get(\'Psr\\\Container\\\ContainerInterface\'),
)',
        ], $compiledObject->getStatements());
    }
}
