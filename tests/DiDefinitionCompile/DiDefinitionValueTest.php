<?php

declare(strict_types=1);

namespace Tests\DiDefinitionCompile;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\DiDefinitionCompile\Fixtures\DiValue\FooEnum;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class DiDefinitionValueTest extends TestCase
{
    /**
     * @dataProvider scalarValueAndNullProvider
     */
    public function testCompileScalarOrNullOrEnum(mixed $definition, string $expect): void
    {
        self::assertEqualsIgnoringCase($expect, (new DiDefinitionValue($definition))->compile());
    }

    public function scalarValueAndNullProvider(): Generator
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

    /**
     * @dataProvider scalarAndNullInArrayProvider
     */
    public function testCompileScalarAndNullInArray(array $definition, string $expect): void
    {
        self::assertEqualsIgnoringCase($expect, (new DiDefinitionValue($definition))->compile());
    }

    public function scalarAndNullInArrayProvider(): Generator
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

    /**
     * @dataProvider failCompileProvider
     */
    public function testFailCompile(mixed $definition, string $expectMessage): void
    {
        $this->expectException(DiDefinitionCompileExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiDefinitionValue($definition))->compile();
    }

    public function failCompileProvider(): Generator
    {
        yield 'object' => [$this, '"'.self::class.'"'];

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
