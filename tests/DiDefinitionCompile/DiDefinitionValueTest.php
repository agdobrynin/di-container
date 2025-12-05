<?php

declare(strict_types=1);

namespace Tests\DiDefinitionCompile;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
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
class DiDefinitionValueTest extends TestCase
{
    #[DataProvider('scalarValueAndNullProvider')]
    public function testCompileScalarOrNullOrEnum(mixed $definition, string $expect): void
    {
        self::assertEqualsIgnoringCase($expect, (new DiDefinitionValue($definition))->compile());
    }

    public static function scalarValueAndNullProvider(): Generator
    {
        yield 'string value' => ['Lorem ipsum dolor sit amet, consectetur adipiscing elit.', '\'Lorem ipsum dolor sit amet, consectetur adipiscing elit.\''];

        yield 'int value' => [1_000_000, '1000000'];

        yield 'int negative value' => [-100, '-100'];

        yield 'float value' => [100_000.256, '100000.256'];

        yield 'bool value' => [true, 'true'];

        yield 'null value' => [null, 'null'];

        $v = (PHP_VERSION_ID < 80200)
            ? 'Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Baz'
            : '\Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Baz';

        yield 'enum value' => [FooEnum::Baz, $v];
    }

    #[DataProvider('scalarAndNullInArrayProvider')]
    public function testCompileScalarAndNullInArray(array $definition, string $expect): void
    {
        self::assertEqualsIgnoringCase($expect, (new DiDefinitionValue($definition))->compile());
    }

    public static function scalarAndNullInArrayProvider(): Generator
    {
        yield 'empty array' => [[], 'array (
)'];

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
            ], 'array (
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
      '.(PHP_VERSION_ID >= 80200 ? '\\' : '').'Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Baz,
    ),
  ),
  1 => 
  '.(PHP_VERSION_ID >= 80200 ? '\\' : '').'Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum::Bar,
)', ];
    }

    #[DataProvider('failCompileProvider')]
    public function testFailCompile(mixed $definition, string $expectMessage): void
    {
        $this->expectException(DiDefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiDefinitionValue($definition))->compile();
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
