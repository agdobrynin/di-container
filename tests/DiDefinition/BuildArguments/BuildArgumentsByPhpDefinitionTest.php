<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use ArrayIterator;
use Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use stdClass;
use Tests\DiDefinition\BuildArguments\Fixtures\Bar;
use Tests\DiDefinition\BuildArguments\Fixtures\Baz;
use Tests\DiDefinition\BuildArguments\Fixtures\Foo;
use Tests\DiDefinition\BuildArguments\Fixtures\Quux;
use Tests\DiDefinition\BuildArguments\Fixtures\QuuxInterface;

use function array_keys;
use function func_get_args;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 *
 * @internal
 */
class BuildArgumentsByPhpDefinitionTest extends TestCase
{
    use BindArgumentsTrait;

    private DiContainerInterface $container;

    public function setUp(): void
    {
        $this->bindArguments();
        $this->container = $this->createMock(DiContainerInterface::class);
    }

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->bindArguments('one', 'two', diGet('services.logger_file'));

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $ba->build(false)
        );
    }

    public function testGetWithoutParametersAndNamedArgument(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $service');

        $fn = static fn () => '';

        $this->bindArguments('one', 'two', service: diGet('services.logger_file'));

        (new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container))
            ->build(false)
        ;
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $dto = new stdClass()): array => [$iterator, $dto];

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        self::assertEquals([0 => diGet('ArrayIterator')], $args);
    }

    public function testGetParameterTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEmpty($ba->build(false));
    }

    public function testGetParameterTypeOnce(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $this->container->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, false],
            ])
        ;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            [0 => diGet(Bar::class)],
            $ba->build(false)
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEmpty($ba->build(false));
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $ba->build(false);
    }

    public function testVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        self::assertCount(1, $args);
        // Order arg name (key) important
        self::assertEquals(diGet(Bar::class), $args[0]);
    }

    public function testDefaultValueAndVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar&Foo $bar = new Baz(), Foo ...$foo): array => [$bar, $foo];

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        self::assertCount(0, $args);
    }

    public function testDefaultValueAndVariadicParameterArgument(): void
    {
        $fn = static fn (Bar&Foo $bar = new Baz(), Foo ...$foo): array => [$bar, $foo];

        $this->bindArguments(
            foo: diGet(Foo::class),
            foo_1: diGet(Baz::class),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        // Order arg name (key) important
        self::assertEquals(['foo', 'foo_1'], array_keys($args));
        // definition resolved
        self::assertEquals(diGet(Foo::class), $args['foo']);
        self::assertEquals(diGet(Baz::class), $args['foo_1']);
    }

    public function testVariadicParameterBindArgumentAsNamedArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->bindArguments(
            foo: diGet(Foo::class),
            foo_1: diGet(Baz::class),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

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

        $this->bindArguments(
            foo_foo: diGet(Foo::class),
            foo_baz: diGet(Baz::class),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

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

        $this->bindArguments(
            foo_foo: diGet(Foo::class),
            foo: diGet(Baz::class),
            bar: diGet(Bar::class),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        // Order arg name (key) important
        self::assertEquals([0, 'foo_foo', 'foo'], array_keys($args));
        // definition resolved
        self::assertEquals(diGet(Bar::class), $args[0]);
        self::assertEquals(diGet(Baz::class), $args['foo']);
        self::assertEquals(diGet(Foo::class), $args['foo_foo']);
    }

    public function testVariadicParameterBindArgumentAsIndexArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->bindArguments(
            diGet(Bar::class),
            diGet(Foo::class),
            diGet(Baz::class),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        // Order arg name (key) important
        self::assertEquals(diGet(Bar::class), $args[0]);
        self::assertEquals(diGet(Foo::class), $args[1]);
        self::assertEquals(diGet(Baz::class), $args[2]);
    }

    public function testDefaultParameter(): void
    {
        $fn = static fn (Foo $foo, Bar|Foo $bar = new Baz()): array => [$foo, $bar];

        $this->container->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            [diGet(Foo::class)],
            $ba->build(false)
        );
    }

    public function testPassNotDeclarationParameter(): void
    {
        $fn = static fn (Foo $foo): array => [$foo, func_get_args()];

        $this->bindArguments(
            diGet(Foo::class),
            diAutowire(Baz::class)
                ->bindArguments('secure_string')
                ->setup('__invoke'),
            diValue(new stdClass()),
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        self::assertEquals(
            [
                0 => diGet(Foo::class),
                1 => diAutowire(Baz::class)
                    ->bindArguments('secure_string')
                    ->setup('__invoke'),
                2 => diValue(new stdClass()),
            ],
            $args
        );
    }

    public function testSecondDefaultValueAndVariadic(): void
    {
        $fn = static fn (
            QuuxInterface $quux,        // parameter #0
            Bar|Foo $bar = new Baz(),   // parameter #1
            Baz ...$baz,                // parameter #2
        ) => true;

        $this->bindArguments(
            quux: diGet(Quux::class)
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build(false);

        // argument order is important
        self::assertCount(1, $args);
        self::assertEquals(diGet(Quux::class), $args[0]);
    }

    public function testBindSimpleArgument(): void
    {
        $this->bindArguments(secure: 'secure_string');

        $ba = new BuildArguments(
            $this->getBindArguments(),
            (new ReflectionClass(Quux::class))->getConstructor(),
            $this->container,
        );

        $args = $ba->build(false);

        self::assertCount(1, $args);
        self::assertEquals('secure_string', $args[0]);
    }

    public function testTailArgs(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $other_one');

        $fn = static fn (string $str) => func_get_args();

        $this->bindArguments(
            str: diGet('params.secure_string'),
            other_one: diGet('services.baz'),
            other_two: diGet('services.bar')
        );

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $ba->build(false);
    }
}
