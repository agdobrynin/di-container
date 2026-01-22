<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ValueEntry;

use Generator;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Compiler\CompilableDefinition\ValueEntry\Fixtures\FooBarEnum;

/**
 * @internal
 */
#[CoversClass(ValueEntry::class)]
#[CoversClass(CompiledEntry::class)]
class ValueEntryTest extends TestCase
{
    #[DataProvider('dataProvideFailCompile')]
    public function testFailCompile(mixed $def): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);

        (new ValueEntry($def))->compile('$this');
    }

    public static function dataProvideFailCompile(): Generator
    {
        yield 'object' => [new stdClass()];

        yield 'object in array' => [[
            'foo' => 'bar',
            'bar' => 100,
            'qux' => [
                'quux' => [
                    'quuz' => new stdClass(),
                ],
            ],
        ]];
    }

    public function testGetDefinition(): void
    {
        self::assertEquals('Lorem ipsum', (new ValueEntry('Lorem ipsum'))->getDiDefinition());
    }

    #[DataProvider('dataProvideCompile')]
    public function testCompile(mixed $definition, string $expression, string $returnType): void
    {
        $ce = (new ValueEntry($definition))->compile('$this');

        self::assertEquals($expression, $ce->getExpression());
        self::assertEquals($returnType, $ce->getReturnType());
        self::assertEquals([], $ce->getStatements());
        self::assertEquals('$object', $ce->getScopeServiceVar());
        self::assertEquals(['$object'], $ce->getScopeVars());
    }

    public static function dataProvideCompile(): Generator
    {
        yield 'string' => ['Lorem ipsum', '\'Lorem ipsum\'', 'string'];

        yield 'integer' => [100_100, '100100', 'int'];

        yield 'float' => [3.14, '3.14', 'float'];

        yield 'boolean' => [false, 'false', 'bool'];

        yield 'null' => [null, 'NULL', 'null'];

        yield 'enum' => [FooBarEnum::Bar, '\Tests\Compiler\CompilableDefinition\ValueEntry\Fixtures\FooBarEnum::Bar', '\Tests\Compiler\CompilableDefinition\ValueEntry\Fixtures\FooBarEnum'];

        yield 'complex array' => [
            ['foo' => 'bar', ['bar' => 100, true, null], 'qux' => ['enum' => FooBarEnum::Bar, 'quux' => 3.14]],
            '[
  \'foo\' => \'bar\',
  0 => [
    \'bar\' => 100,
    0 => true,
    1 => NULL,
  ],
  \'qux\' => [
    \'enum\' => \Tests\Compiler\CompilableDefinition\ValueEntry\Fixtures\FooBarEnum::Bar,
    \'quux\' => 3.14,
  ],
]',
            'array',
        ];
    }
}
