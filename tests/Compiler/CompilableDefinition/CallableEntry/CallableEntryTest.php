<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\CallableEntry;

use Generator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\CallableEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\ArgumentBuilderException;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures\Bar;
use Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures\Foo;

use function explode;
use function is_array;
use function is_string;
use function rand;
use function strpos;

/**
 * @internal
 */
#[CoversClass(CallableEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ValueEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(DiDefinitionTransformer::class)]
#[CoversClass(\Kaspi\DiContainer\Compiler\Helper::class)]
#[CoversClass(GetEntry::class)]
#[CoversClass(DiDefinitionGet::class)]
class CallableEntryTest extends TestCase
{
    private FinderClosureCodeInterface $mockFinderClosure;
    private DiDefinitionCallableInterface $mockDefinition;
    private DiContainerDefinitionsInterface $mockDiContainerDefinitions;
    private DiDefinitionTransformerInterface $transformer;

    public function setUp(): void
    {
        $this->mockFinderClosure = $this->createMock(FinderClosureCodeInterface::class);
        $this->mockDefinition = $this->createMock(DiDefinitionCallableInterface::class);
        $this->mockDiContainerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
        $this->transformer = new DiDefinitionTransformer($this->mockFinderClosure);
    }

    public function tearDown(): void
    {
        unset($this->mockFinderClosure, $this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer);
    }

    public function testGetDefinition(): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn('\log')
        ;

        $ce = new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer);

        self::assertIsCallable($ce->getDiDefinition()->getDefinition());
    }

    public function testFailCompileCallableEntryWithObject(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Definition [$object, "getName"]');

        $this->mockDefinition->method('getDefinition')
            ->willReturn([new Foo('Lorem ipsum'), 'getName'])
        ;

        (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;
    }

    #[DataProvider('dataProviderForExposeArgBuilder')]
    public function testFailExposeArgBuilder(callable $rawDefinition): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot provide arguments to a callable definition');

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willThrowException(new DiDefinitionException())
        ;
        $this->mockDefinition->method('getDefinition')
            ->willReturn($rawDefinition)
        ;

        (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;
    }

    #[DataProvider('dataProviderForExposeArgBuilder')]
    public function testFailBuildArguments(callable $rawDefinition): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot build arguments for callable definition');

        $rFn = self::reflectCallableHelper($rawDefinition);

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;
        $mockArgumentBuilder->method('build')
            ->willThrowException(new ArgumentBuilderException())
        ;

        $this->mockDefinition->method('getDefinition')
            ->willReturn($rawDefinition)
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;
    }

    public static function dataProviderForExposeArgBuilder(): Generator
    {
        yield 'callable class present as array' => [[Bar::class, 'baz']];

        yield 'callable class present as string' => [Bar::class.'::baz'];

        yield 'callable function' => ['\log'];

        yield 'Closure function' => [static fn (Foo $foo) => $foo->getName().rand(1, 100)];
    }

    public function testCompileFunction(): void
    {
        $rawDefinition = 'log';
        $this->mockDefinition->method('getDefinition')
            ->willReturn('log')
        ;

        $rFn = self::reflectCallableHelper($rawDefinition);

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;
        $mockArgumentBuilder->method('build')
            ->willReturn([100])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        $ce = (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this', ['$service'])
        ;

        self::assertEquals('\log(
  100,
)', $ce->getExpression());
        self::assertEquals('mixed', $ce->getReturnType());
        self::assertEquals('$closure', $ce->getScopeServiceVar());
        self::assertEquals(['$service', '$this', '$closure', '$object'], $ce->getScopeVars());
        self::assertEquals([], $ce->getStatements());
    }

    #[DataProvider('dataProviderClassWithMethod')]
    public function testCompileClassCallableAsString(callable $rawDefinition): void
    {
        $this->mockDefinition->method('getDefinition')
            ->willReturn($rawDefinition)
        ;

        $rFn = self::reflectCallableHelper($rawDefinition);

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;
        $mockArgumentBuilder->method('build')
            ->willReturn([
                'foo' => new DiDefinitionGet(Foo::class),
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        $ce = (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;

        self::assertEquals('[\Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures\Bar::class, \'baz\'](
  foo: $this->get(\'Tests\\\Compiler\\\CompilableDefinition\\\CallableEntry\\\Fixtures\\\Foo\'),
)', $ce->getExpression());
        self::assertEquals('mixed', $ce->getReturnType());
        self::assertEquals('$closure', $ce->getScopeServiceVar());
        self::assertEquals(['$this', '$closure', '$object'], $ce->getScopeVars());
        self::assertEquals([], $ce->getStatements());
    }

    public static function dataProviderClassWithMethod(): Generator
    {
        yield 'callable class present as array' => [[Bar::class, 'baz']];

        yield 'callable class present as string' => [Bar::class.'::baz'];
    }

    public function testCompileClosure(): void
    {
        $rawDefinition = static fn (Foo $foo) => $foo->getName().rand(1, 100);

        $closureCodeResult = 'static fn (\Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures\Foo $foo) => $foo->getName().\rand(1, 100)';

        $this->mockFinderClosure->method('getCode')
            ->willReturn($closureCodeResult)
        ;

        $this->mockDefinition->method('getDefinition')
            ->willReturn($rawDefinition)
        ;

        $rFn = self::reflectCallableHelper($rawDefinition);

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;
        $mockArgumentBuilder->method('build')
            ->willReturn([
                0 => new DiDefinitionGet(Foo::class),
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        $ce = (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;

        self::assertEquals('('.$closureCodeResult.')(
  $this->get(\'Tests\\\Compiler\\\CompilableDefinition\\\CallableEntry\\\Fixtures\\\Foo\'),
)', $ce->getExpression());
        self::assertEquals('mixed', $ce->getReturnType());
        self::assertEquals('$closure', $ce->getScopeServiceVar());
        self::assertEquals(['$this', '$closure', '$object'], $ce->getScopeVars());
        self::assertEquals([], $ce->getStatements());
    }

    public function testCompileClosureFailGetCode(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('closure expression cannot be parsed');

        $rawDefinition = static fn (Foo $foo) => $foo->getName().rand(1, 100);

        $this->mockFinderClosure->method('getCode')
            ->willThrowException(new LogicException())
        ;

        $this->mockDefinition->method('getDefinition')
            ->willReturn($rawDefinition)
        ;

        $rFn = self::reflectCallableHelper($rawDefinition);

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $this->transformer))
            ->compile('$this')
        ;
    }

    public function testCannotCompileArgumentExpression(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile arguments for callable definition');

        $this->mockDefinition->method('getDefinition')
            ->willReturn('log')
        ;

        $rFn = self::reflectCallableHelper('log');

        $mockArgumentBuilder = $this->createMock(ArgumentBuilderInterface::class);
        $mockArgumentBuilder->method('getFunctionOrMethod')
            ->willReturn($rFn)
        ;

        $mockArgumentBuilder->method('build')
            ->willReturn([
                0 => 100,
            ])
        ;

        $this->mockDefinition->method('exposeArgumentBuilder')
            ->willReturn($mockArgumentBuilder)
        ;

        $transformerMock = $this->createMock(DiDefinitionTransformerInterface::class);
        $transformerMock->method('transform')
            ->willThrowException(new DefinitionCompileException())
        ;

        (new CallableEntry($this->mockDefinition, $this->mockDiContainerDefinitions, $transformerMock))
            ->compile('$this')
        ;
    }

    private static function reflectCallableHelper(callable $callable): ReflectionFunctionAbstract
    {
        return match (true) {
            is_string($callable) && strpos($callable, '::') > 0 => new ReflectionMethod(...explode('::', $callable, 2)),
            is_array($callable) => new ReflectionMethod($callable[0], $callable[1]),
            default => new ReflectionFunction($callable),
        };
    }
}
