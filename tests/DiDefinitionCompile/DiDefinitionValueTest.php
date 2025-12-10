<?php

declare(strict_types=1);

namespace Tests\DiDefinitionCompile;

use Generator;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\DiDefinitionCompile\Fixtures\DiValue\Foo;
use Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum;

/**
 * @internal
 */
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(CompiledEntry::class)]
class DiDefinitionValueTest extends TestCase
{
    #[DataProvider('scalarValueAndNullProvider')]
    public function testCompileScalarOrNullOrEnum(mixed $definition, array $expect): void
    {
        $compiledEntry = (new DiDefinitionValue($definition))
            ->compile('', $this->createMock(DiContainerInterface::class))
        ;

        self::assertEquals(
            [
                ...$expect,
                'statements' => '',
                'scope_variables' => [],
                'is_singleton' => null,
            ],
            [
                'expression' => $compiledEntry->getExpression(),
                'return_type' => $compiledEntry->getReturnType(),
                'statements' => $compiledEntry->getStatements(),
                'scope_variables' => $compiledEntry->getScopeVariables(),
                'is_singleton' => $compiledEntry->isSingleton(),
            ]
        );
    }

    public static function scalarValueAndNullProvider(): Generator
    {
        yield 'string value' => [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            [
                'expression' => '\'Lorem ipsum dolor sit amet, consectetur adipiscing elit.\'',
                'return_type' => 'string',
            ],
        ];

        yield 'int value' => [
            1_000_000,
            [
                'expression' => '1000000',
                'return_type' => 'int',
            ],
        ];

        yield 'int negative value' => [
            -100,
            [
                'expression' => '-100',
                'return_type' => 'int',
            ],
        ];

        yield 'float value' => [
            100_000.256,
            [
                'expression' => '100000.256',
                'return_type' => 'float',
            ],
        ];

        yield 'bool value' => [
            true,
            [
                'expression' => 'true',
                'return_type' => 'bool',
            ],
        ];

        yield 'null value' => [
            null,
            [
                'expression' => 'NULL',
                'return_type' => 'null',
            ],
        ];

        yield 'enum value' => [
            FooEnum::Baz,
            [
                'expression' => '\Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Baz',
                'return_type' => '\Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum',
            ],
        ];

        yield 'empty array' => [
            [], [
                'expression' => 'array (
)',
                'return_type' => 'array',
            ],
        ];

        yield 'array' => [
            [
                'foo' => 'bar',
                200_000,
                'null' => null,
                'sub_arr' => [
                    [
                        'bool' => true,
                        'foo' => 'bar',
                        200_000,
                        'null' => null,
                        'enum' => FooEnum::Baz,
                    ],
                ],
                FooEnum::Bar,
            ],
            [
                'expression' => 'array (
  \'foo\' => \'bar\',
  0 => 200000,
  \'null\' => NULL,
  \'sub_arr\' => 
  array (
    0 => 
    array (
      \'bool\' => true,
      \'foo\' => \'bar\',
      0 => 200000,
      \'null\' => NULL,
      \'enum\' => 
      \Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Baz,
    ),
  ),
  1 => 
  \Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Bar,
)',
                'return_type' => 'array',
            ],
        ];
    }

    #[DataProvider('failCompileProvider')]
    public function testFailCompile(mixed $definition, string $expectMessage): void
    {
        $this->expectException(DiDefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiDefinitionValue($definition))->compile('', $this->createMock(DiContainerInterface::class));
    }

    public static function failCompileProvider(): Generator
    {
        yield 'object' => [new Foo(), '"'.Foo::class.'"'];

        yield 'array with object' => [
            [
                'foo' => 'bar',
                'other_foo' => [
                    'foo' => 'bar',
                    'sub_foo' => [
                        'enum' => FooEnum::Baz,
                        'sub_sub_foo' => [
                            'test' => new stdClass(),
                        ],
                    ],
                ],
            ],
            '"stdClass"',
        ];
    }
}
