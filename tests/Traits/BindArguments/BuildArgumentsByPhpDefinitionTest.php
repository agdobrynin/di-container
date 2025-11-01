<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments;

use ArrayIterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use stdClass;
use Tests\Traits\BindArguments\Fixtures\Bar;
use Tests\Traits\BindArguments\Fixtures\Baz;
use Tests\Traits\BindArguments\Fixtures\Foo;

use function array_keys;
use function func_get_args;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class BuildArgumentsByPhpDefinitionTest extends TestCase
{
    use BindArgumentsTrait;
    use DiContainerTrait;
    private DiContainerInterface $containerMock;

    public function setUp(): void
    {
        $this->bindArguments();
        $this->containerMock = $this->createMock(DiContainerInterface::class);
    }

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->setContainer($this->containerMock);
        $this->bindArguments('one', 'two', diGet('services.logger_file'));

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testGetWithoutParametersAndNamedArgument(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $service');

        $fn = static fn () => '';

        $this->setContainer($this->containerMock);
        $this->bindArguments(
            'one',
            'two',
            service: diGet('services.logger_file')
        );

        $this->buildArguments(new ReflectionFunction($fn), false);
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $dto = new stdClass()): array => [$iterator, $dto];

        $this->setContainer($this->containerMock);

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        self::assertEquals([0 => diGet('ArrayIterator')], $args);
    }

    public function testGetParameterTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $this->setContainer($this->containerMock);

        self::assertEmpty($this->buildArguments(new ReflectionFunction($fn), false));
    }

    public function testGetParameterTypeOnce(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, false],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEquals(
            [0 => diGet(Bar::class)],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $this->setContainer($this->containerMock);

        self::assertEmpty($this->buildArguments(new ReflectionFunction($fn), false));
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;

        $this->setContainer($this->containerMock);

        $this->buildArguments(new ReflectionFunction($fn), false);
    }

    public function testVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        self::assertCount(1, $args);
        // Order arg name (key) important
        self::assertEquals(diGet(Bar::class), $args[0]);
    }

    public function testVariadicParameterBindArgumentAsNamedArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            foo: diGet(Foo::class),
            foo_1: diGet(Baz::class),
        );

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        // Order arg name (key) important
        self::assertEquals([0, 'foo', 'foo_1'], array_keys($args));
        // definition resolved
        self::assertEquals(diGet(Bar::class), $args[0]);
        self::assertEquals(diGet(Foo::class), $args['foo']);
        self::assertEquals(diGet(Baz::class), $args['foo_1']);
    }

    public function testVariadicParameterBindArgumentAsRandomName(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            foo_foo: diGet(Foo::class),
            foo_baz: diGet(Baz::class),
        );

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        // Order arg name (key) important
        self::assertEquals([0, 'foo_foo', 'foo_baz'], array_keys($args));
        // definition resolved
        self::assertEquals(diGet(Bar::class), $args[0]);
        self::assertEquals(diGet(Foo::class), $args['foo_foo']);
        self::assertEquals(diGet(Baz::class), $args['foo_baz']);
    }

    public function testVariadicParameterBindArgumentAsUnorderNamedArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            foo_foo: diGet(Foo::class),
            foo: diGet(Baz::class),
            bar: diGet(Bar::class),
        );

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        // Order arg name (key) important
        self::assertEquals(['bar', 'foo_foo', 'foo'], array_keys($args));
        // definition resolved
        self::assertEquals(diGet(Bar::class), $args['bar']);
        self::assertEquals(diGet(Baz::class), $args['foo']);
        self::assertEquals(diGet(Foo::class), $args['foo_foo']);
    }

    public function testVariadicParameterBindArgumentAsIndexArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            diGet(Bar::class),
            diGet(Foo::class),
            diGet(Baz::class),
        );

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        // Order arg name (key) important
        self::assertEquals(diGet(Bar::class), $args[0]);
        self::assertEquals(diGet(Foo::class), $args[1]);
        self::assertEquals(diGet(Baz::class), $args[2]);
    }

    public function testDefaultParameter(): void
    {
        $fn = static fn (Foo $foo, Bar|Foo $bar = new Baz()): array => [$foo, $bar];

        $this->containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEquals(
            [diGet(Foo::class)],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testPassNotDeclarationParameter(): void
    {
        $fn = static fn (Foo $foo): array => [$foo, func_get_args()];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            diGet(Foo::class),
            diAutowire(Baz::class)
                ->bindArguments('secure_string')
                ->setup('__invoke'),
            diValue(new stdClass()),
        );

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        self::assertEquals(
            [
                diGet(Foo::class),
                diAutowire(Baz::class)
                    ->bindArguments('secure_string')
                    ->setup('__invoke'),
                diValue(new stdClass()),
            ],
            $args
        );
    }

    public function testTailArgs(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $other');

        $fn = static fn (string $str) => func_get_args();

        $this->setContainer($this->containerMock);
        $this->bindArguments(
            str: diGet('params.secure_string'),
            other_one: diGet('services.baz'),
            other_two: diGet('services.bar')
        );

        // Php attribute priority = true
        $this->buildArguments(new ReflectionFunction($fn), true);
    }
}
