<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ParameterRuntimeEntry;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ParameterRuntimeEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterRuntimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @internal
 */
#[CoversClass(ParameterRuntimeEntry::class)]
#[CoversClass(CompiledEntry::class)]
class ParameterRuntimeEntryTest extends TestCase
{
    #[TestWith(['getDefinition', ''])]
    #[TestWith(['getContext', ''])]
    #[TestWith(['getContext', null])]
    public function testEmptyParameterName(string $methodDefinition, mixed $methodWillReturn): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage('Parameter name must be non-empty string.');

        $parameter = $this->createMock(DiDefinitionParameterRuntimeInterface::class);
        $parameter->method($methodDefinition)->willReturn($methodWillReturn);

        $entry = new ParameterRuntimeEntry($parameter);
        self::assertEquals($methodWillReturn, $entry->getDiDefinition()->{$methodDefinition}());
        $entry->compile('$this');
    }

    #[TestWith(['getDefinition', 'foo'])]
    #[TestWith(['getContext', 'foo'])]
    public function testUniqueName(string $methodDefinition, string $methodWillReturn): void
    {
        $parameter = $this->createMock(DiDefinitionParameterRuntimeInterface::class);
        $parameter->method($methodDefinition)->willReturn($methodWillReturn);

        $entry = new ParameterRuntimeEntry($parameter);
        $ce = $entry->compile('$this', ['$parameters']);

        self::assertEquals('$parameters1', $ce->getScopeServiceVar());
        self::assertEquals('$parameters1->get(\''.$methodWillReturn.'\')', $ce->getExpression());

        $statements = implode(';'.PHP_EOL, $ce->getStatements());
        self::assertStringContainsString('if (!$parameters1->has(\''.$methodWillReturn.'\')) throw new', $statements);
    }
}
