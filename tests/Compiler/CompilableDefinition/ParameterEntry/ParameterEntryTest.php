<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ParameterEntry;

use Generator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ParameterEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @internal
 */
#[CoversClass(ParameterEntry::class)]
#[CoversClass(ParameterNotFoundException::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(Helper::class)]
#[CoversClass(CompiledEntry::class)]
class ParameterEntryTest extends TestCase
{
    private DiContainerDefinitionsInterface $containerDefinitions;

    protected function setUp(): void
    {
        $this->containerDefinitions = $this->createMock(DiContainerDefinitionsInterface::class);
    }

    protected function tearDown(): void
    {
        unset($this->containerDefinitions);
    }

    public function testWithoutName(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile container parameter definition. Parameter name must be non-empty string');

        $parameter = $this->createMock(DiDefinitionParameterInterface::class);
        $parameter->method('getDefinition')->willReturn('');

        (new ParameterEntry($parameter, $this->containerDefinitions))
            ->compile('$this')
        ;
    }

    public function testWithoutNameAndContext(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile container parameter definition. Parameter name must be non-empty string');

        $parameter = $this->createMock(DiDefinitionParameterInterface::class);
        $parameter->method('getDefinition')->willReturn('');
        $parameter->method('getContext')->willReturn('');

        (new ParameterEntry($parameter, $this->containerDefinitions))
            ->compile('$this')
        ;
    }

    #[TestWith([new ParameterNotFoundException(), 'foo', null])]
    #[TestWith([new ParameterException(), '', 'foo'])]
    public function testParameterRetrieveHasException(
        Throwable $parameterRetrieveException,
        string $parameterName,
        ?string $context,
    ): void {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('An error occurred when receiving the value of the container parameter "foo"');

        $sourceParams = $this->createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->with('foo')
            ->willThrowException($parameterRetrieveException)
        ;

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $parameter = $this->createMock(DiDefinitionParameterInterface::class);
        $parameter->method('getDefinition')->willReturn($parameterName);
        $parameter->method('getContext')->willReturn($context);

        $this->containerDefinitions->method('getContainer')
            ->willReturn($container)
        ;

        (new ParameterEntry($parameter, $this->containerDefinitions))
            ->compile('$this')
        ;
    }

    public function testCannotExportValueFromParameter(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile the value of the container parameter "foo"');

        $sourceParams = $this->createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->with('foo')
            ->willReturn(['val' => (object) []])
        ;

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $parameter = $this->createMock(DiDefinitionParameterInterface::class);
        $parameter->method('getDefinition')->willReturn('foo');

        $this->containerDefinitions->method('getContainer')
            ->willReturn($container)
        ;

        (new ParameterEntry($parameter, $this->containerDefinitions))
            ->compile('$this')
        ;
    }

    #[DataProvider('provideCompiledEntryFromParameter')]
    public function testCompiledEntryFromParameter(string $paramName, mixed $paramValue, string $expectExpression, string $expectReturnType): void
    {
        $sourceParams = $this->createMock(SourceParametersMutableInterface::class);
        $sourceParams->method('get')
            ->with($paramName)
            ->willReturn($paramValue)
        ;

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('parameters')
            ->willReturn($sourceParams)
        ;

        $parameter = $this->createMock(DiDefinitionParameterInterface::class);
        $parameter->method('getDefinition')->willReturn($paramName);

        $this->containerDefinitions->method('getContainer')
            ->willReturn($container)
        ;

        $ce = ($parameter = new ParameterEntry($parameter, $this->containerDefinitions))
            ->compile('$this')
        ;

        self::assertEquals($expectReturnType, $ce->getReturnType());
        self::assertEquals($expectExpression, $ce->getExpression());

        self::assertEquals($paramName, $parameter->getDiDefinition()->getDefinition());
    }

    public static function provideCompiledEntryFromParameter(): Generator
    {
        yield 'string value' => [
            'paramName' => 'foo',
            'paramValue' => 'bar',
            'expectExpression' => '\'bar\'',
            'expectReturnType' => 'string',
        ];

        yield 'integer value' => [
            'paramName' => 'foo',
            'paramValue' => 12,
            'expectExpression' => '12',
            'expectReturnType' => 'int',
        ];

        yield 'float value' => [
            'paramName' => 'foo',
            'paramValue' => 3.14,
            'expectExpression' => '3.14',
            'expectReturnType' => 'float',
        ];

        yield 'boolean value' => [
            'paramName' => 'foo',
            'paramValue' => true,
            'expectExpression' => 'true',
            'expectReturnType' => 'bool',
        ];

        yield 'null value' => [
            'paramName' => 'foo',
            'paramValue' => null,
            'expectExpression' => 'NULL',
            'expectReturnType' => 'null',
        ];

        yield 'array value' => [
            'paramName' => 'foo',
            'paramValue' => [
                'foo' => 'bar',
                'baz' => TestEnum::ONE,
                'qux' => [true, false],
            ],
            'expectExpression' => '[
  \'foo\' => \'bar\',
  \'baz\' => \Tests\Compiler\CompilableDefinition\ParameterEntry\TestEnum::ONE,
  \'qux\' => [
    0 => true,
    1 => false,
  ],
]',
            'expectReturnType' => 'array',
        ];

        yield 'enum value' => [
            'paramName' => 'foo',
            'paramValue' => TestEnum::ONE,
            'expectExpression' => '\Tests\Compiler\CompilableDefinition\ParameterEntry\TestEnum::ONE',
            'expectReturnType' => '\Tests\Compiler\CompilableDefinition\ParameterEntry\TestEnum',
        ];
    }
}

enum TestEnum
{
    case ONE;
}
