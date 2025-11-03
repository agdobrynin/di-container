<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use Closure;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\DiDefinition\BuildArguments\Fixtures\Bar;
use Tests\DiDefinition\BuildArguments\Fixtures\Baz;
use Tests\DiDefinition\BuildArguments\Fixtures\Foo;
use Tests\DiDefinition\BuildArguments\Fixtures\HeavyDependency;
use Tests\DiDefinition\BuildArguments\Fixtures\HeavyDependencyTwo;
use Tests\DiDefinition\BuildArguments\Fixtures\Quux;
use Tests\DiDefinition\BuildArguments\Fixtures\QuuxInterface;
use Tests\DiDefinition\BuildArguments\Fixtures\QuuxTwo;

use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 *
 * @internal
 */
class BuildArgumentsByPhpAttributeTest extends TestCase
{
    use BindArgumentsTrait;

    private DiContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(DiContainerInterface::class);
        $this->bindArguments();
    }

    public function testInjectRegularParametersAttributeHigherPriority(): void
    {
        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->bindArguments(quux: diGet('services.quux'));

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        // argument resolve by php attribute
        self::assertEquals([0 => diGet(Quux::class)], $args);

        // bind argument cannot resolve because BuildArguments::build(true)
        self::assertEquals(['quux' => diGet('services.quux')], $this->getBindArguments());
    }

    public function testInjectRegularParametersAndTailArgs(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $other_two');

        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->bindArguments(
            other: diGet('services.foo'),
            other_two: diGet('services.bar'),
            other_three: diGet('services.baz'),
        );
        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $ba->basedOnPhpAttributes();
    }

    public function testInjectRegularParametersPhpDefinitionHigherPriority(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux, #[Inject(Baz::class)] Foo $foo) => $quux;

        $this->bindArguments(quux: diGet('services.quux'));

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
        $args = $ba->basedOnBindArgumentsAsPriorityAndPhpAttributes();

        self::assertEquals(
            [
                0 => diGet('services.quux'),
                1 => diGet(Baz::class),
            ],
            $args
        );
    }

    public function testInjectRegularParameters(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux) => $quux;
        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals([0 => diGet(Quux::class)], $args);
    }

    public function testInjectVariadicParameters(): void
    {
        $fn = static fn (
            Baz $baz,
            #[Inject(Quux::class), Inject(QuuxTwo::class)]
            QuuxInterface ...$quux
        ) => $quux;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        // Order arg important
        self::assertEquals(diGet(Baz::class), $args[0]);
        self::assertEquals(diGet(Quux::class), $args[1]);
        self::assertEquals(diGet(QuuxTwo::class), $args[2]);
    }

    public function testProxyClosureRegularParameters(): void
    {
        /**
         * @param Closure(): HeavyDependency $heavyDependency
         */
        $fn = static fn (
            #[ProxyClosure(HeavyDependency::class)]
            Closure $heavyDependency,
            QuuxInterface $quux,
        ) => ($heavyDependency)()->doMake($quux);

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diProxyClosure(HeavyDependency::class),
                1 => diGet(QuuxInterface::class),
            ],
            $args,
        );
    }

    public function testProxyClosureVariadicParameters(): void
    {
        /**
         * @param Closure(): HeavyDependency $heavyDependency
         */
        $fn = static fn (
            QuuxInterface $quux,
            #[
                ProxyClosure(HeavyDependency::class),
                ProxyClosure(HeavyDependencyTwo::class),
            ]
            Closure ...$heavyDependency,
        ): array => [($heavyDependency[0])()->doMake($quux), ($heavyDependency[1])()->doMake($quux)];

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diProxyClosure(HeavyDependency::class),
                2 => diProxyClosure(HeavyDependencyTwo::class),
            ],
            $args,
        );
    }

    public function testCallableRegularParameters(): void
    {
        $fn = static fn (
            QuuxInterface $quux,
            #[InjectByCallable(Baz::class.'::doMake')]
            callable $doCallable,
        ) => ($doCallable)($quux);

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diCallable(Baz::class.'::doMake'),
            ],
            $args,
        );
    }

    public function testCallableVariadicParameters(): void
    {
        $fn = static fn (
            QuuxInterface $quux,
            #[InjectByCallable(Baz::class.'::doMake')]
            #[InjectByCallable('App\Helpers\fn\funcUser')]
            callable ...$doCallable,
        ) => true;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diCallable(Baz::class.'::doMake'),
                2 => diCallable('App\Helpers\fn\funcUser'),
            ],
            $args,
        );
    }

    public function testTaggedAsRegularParameters(): void
    {
        $fn = static fn (
            QuuxInterface $quux,
            #[TaggedAs('tags.validate_string')]
            iterable $validators,
        ) => true;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diTaggedAs('tags.validate_string'),
            ],
            $args,
        );
    }

    public function testTaggedAsVariadicParameters(): void
    {
        $fn = static fn (
            QuuxInterface $quux,
            #[TaggedAs('tags.validator_string')]
            #[TaggedAs('tags.validator_password', priorityDefaultMethod: 'self::getPriority')]
            iterable ...$validator,
        ) => true;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diTaggedAs('tags.validator_string'),
                2 => diTaggedAs('tags.validator_password', priorityDefaultMethod: 'self::getPriority'),
            ],
            $args,
        );
    }

    public function testMixPhpAttributeAndBindArguments(): void
    {
        $fn = static fn (
            #[Inject(Quux::class)]
            QuuxInterface $quux, // parameter #0
            Bar $bar,            // parameter #1
            #[Inject('services.baz')]
            Baz $baz,            // parameter #2
        ) => true;

        $this->bindArguments(bar: diCallable([Baz::class, 'doMake']));

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        // argument order is important
        self::assertEquals(diGet(Quux::class), $args[0]);
        self::assertEquals(diCallable([Baz::class, 'doMake']), $args[1]);
        self::assertEquals(diGet('services.baz'), $args[2]);
    }

    public function testInjectSecondDefaultValueAndVariadic(): void
    {
        $fn = static fn (
            #[Inject(Quux::class)]
            QuuxInterface $quux,        // parameter #0
            Bar|Foo $bar = new Baz(),   // parameter #1
            Baz ...$baz,                // parameter #2
        ) => true;

        $ba = new BuildArguments($this->getBindArguments(), new ReflectionFunction($fn), $this->container);

        $args = $ba->basedOnPhpAttributes();

        // argument order is important
        self::assertCount(1, $args);
        self::assertEquals(diGet(Quux::class), $args[0]);
    }
}
