<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ContainerParametersEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ContainerParametersEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(ContainerParametersEntry::class)]
#[CoversClass(CompiledEntry::class)]
#[CoversClass(Helper::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ParameterNotFoundException::class)]
class ContainerParametersEntryTest extends TestCase
{
    public function testEmptyParameters(): void
    {
        $paramsMock = $this->createMock(SourceParametersMutableInterface::class);
        $paramsMock->method('parameters')
            ->willReturnCallback(static fn () => yield from [])
        ;

        $entry = new ContainerParametersEntry($paramsMock, InvalidBehaviorCompileEnum::RuntimeContainerException);
        $compiledEntry = $entry->compile('$this');

        self::assertEquals([], $compiledEntry->getStatements());
        self::assertEquals('new \Kaspi\DiContainer\Parameters\ImmediateSourceParameters()', $compiledEntry->getExpression());
    }

    public function testWithParameters(): void
    {
        $paramsMock = $this->createMock(SourceParametersMutableInterface::class);
        $paramsMock->method('parameters')
            ->willReturnCallback(static fn () => yield from [
                'foo' => 'bar',
                'bar' => 'baz',
                'qux' => new ParameterNotFoundException('Parameter "qux" not found.'),
            ])
        ;

        $entry = new ContainerParametersEntry($paramsMock, InvalidBehaviorCompileEnum::RuntimeContainerException);
        $compiledEntry = $entry->compile('$this');

        self::assertEquals([], $compiledEntry->getStatements());
        self::assertEquals('new \Kaspi\DiContainer\Parameters\DeferredSourceParameters(static function (): \Generator {
  yield \'foo\' => \'bar\';
  yield \'bar\' => \'baz\';
  yield \'qux\' => new \Kaspi\DiContainer\Exception\ParameterNotFoundException(message: \'Parameter "qux" not found.\');
})', $compiledEntry->getExpression());
    }

    public function testExceptionOnCompilingProcess(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Parameter "qux" not found.');

        $paramsMock = $this->createMock(SourceParametersMutableInterface::class);
        $paramsMock->method('parameters')
            ->willReturnCallback(static function () {
                yield 'foo' => 'bar';

                yield 'qux' => throw new ParameterNotFoundException('Parameter "qux" not found.');
            })
        ;

        $entry = new ContainerParametersEntry($paramsMock, InvalidBehaviorCompileEnum::ExceptionOnCompile);
        $entry->compile('$this');
    }

    public function testUnsupportedParameterTypeOnCompilingProcess(): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('The value in array is invalid type "stdClass".');

        $paramsMock = $this->createMock(SourceParametersMutableInterface::class);
        $paramsMock->method('parameters')
            ->willReturnCallback(static function () {
                yield 'foo' => 'bar';

                yield 'qux' => [
                    new stdClass(),
                ];
            })
        ;

        $entry = new ContainerParametersEntry($paramsMock, InvalidBehaviorCompileEnum::ExceptionOnCompile);
        $entry->compile('$this');
    }

    public function testUnsupportedParameterTypeOnCompilingProcessForRuntimeContainerException(): void
    {
        $paramsMock = $this->createMock(SourceParametersMutableInterface::class);
        $paramsMock->method('parameters')
            ->willReturnCallback(static function () {
                yield 'foo' => 'bar';

                yield 'qux' => [
                    new stdClass(),
                ];
            })
        ;

        $entry = new ContainerParametersEntry($paramsMock, InvalidBehaviorCompileEnum::RuntimeContainerException);
        $compiledEntry = $entry->compile('$this');

        self::assertEquals([], $compiledEntry->getStatements());
        self::assertEquals(
            'new \Kaspi\DiContainer\Parameters\DeferredSourceParameters(static function (): \Generator {
  yield \'foo\' => \'bar\';
  yield \'qux\' => new \Kaspi\DiContainer\Exception\ParameterException(message: \'Cannot compile container parameter "qux". Reason by: Cannot export type "array". Support only a scalar-type, null value, UnitEnum type or array with that types. The value in array is invalid type "stdClass".\');
})',
            $compiledEntry->getExpression()
        );
    }
}
