<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use ArrayIterator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
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
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
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
        $this->container->method('getConfig')
            ->willReturn(
                new DiContainerConfig(
                    useAttribute: false,
                )
            )
        ;
    }

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->bindArguments('one', 'two', diGet('services.logger_file'));

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $ba->build()
        );
    }

    public function testGetWithoutParametersAndNamedArgument(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $service');

        $fn = static fn () => '';

        $this->bindArguments('one', 'two', service: diGet('services.logger_file'));

        (new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container))
            ->build()
        ;
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $dto = new stdClass()): array => [$iterator, $dto];

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

        self::assertEquals([0 => diGet('ArrayIterator')], $args);
    }

    public function testResolveByParameterWhenConfigSwitchOffUsePhpAttribute(): void
    {
        $fn = static fn (#[Inject('services.arr_iter')] ArrayIterator $iterator): iterable => $iterator;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

        self::assertEquals([0 => diGet('ArrayIterator')], $args);
    }

    public function testGetParameterTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEmpty($ba->build());
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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            [0 => diGet(Bar::class)],
            $ba->build()
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEmpty($ba->build());
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $ba->build();
    }

    public function testVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

        self::assertCount(1, $args);
        // Order arg name (key) important
        self::assertEquals(diGet(Bar::class), $args[0]);
    }

    public function testDefaultValueAndVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar&Foo $bar = new Baz(), Foo ...$foo): array => [$bar, $foo];

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

        self::assertCount(0, $args);
    }

    public function testDefaultValueAndVariadicParameterArgument(): void
    {
        $fn = static fn (Bar&Foo $bar = new Baz(), Foo ...$foo): array => [$bar, $foo];

        $this->bindArguments(
            foo: diGet(Foo::class),
            foo_1: diGet(Baz::class),
        );

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        self::assertEquals(
            [diGet(Foo::class)],
            $ba->build()
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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->build();

        // argument order is important
        self::assertCount(1, $args);
        self::assertEquals(diGet(Quux::class), $args[0]);
    }

    public function testBindSimpleArgument(): void
    {
        $this->bindArguments(secure: 'secure_string');

        $ba = new ArgumentBuilder(
            $this->getBindArguments(),
            (new ReflectionClass(Quux::class))->getConstructor(),
            $this->container,
        );

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $ba->build();
    }
}
