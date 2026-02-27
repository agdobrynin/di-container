<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use Closure;
use DiDefinition\BuildArguments\Fixtures\BazInterface;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\DiDefinition\BuildArguments\Fixtures\Bar;
use Tests\DiDefinition\BuildArguments\Fixtures\BatFactory;
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
 * @internal
 */
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(TaggedAs::class)]
#[CoversClass(ProxyClosure::class)]
#[CoversClass(InjectByCallable::class)]
#[CoversClass(Inject::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversFunction('\Kaspi\DiContainer\diProxyClosure')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(Helper::class)]
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(DiFactory::class)]
#[CoversClass(DiDefinitionFactory::class)]
class BuildArgumentsByPhpAttributeTest extends TestCase
{
    use BindArgumentsTrait;

    private DiContainerInterface $mockContainer;

    public function setUp(): void
    {
        $this->mockContainer = $this->createMock(DiContainerInterface::class);
        $this->mockContainer->method('getConfig')
            ->willReturn(
                new DiContainerConfig(
                    useAttribute: true
                )
            )
        ;
        $this->bindArguments();
    }

    public function testInjectRegularParametersAttributeHigherPriority(): void
    {
        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->bindArguments(quux: diGet('services.quux'));

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

        // argument resolve by php attribute
        self::assertEquals([0 => diGet(Quux::class)], $args);

        // bind argument cannot resolve because BuildArguments::build(true)
        self::assertEquals(['quux' => diGet('services.quux')], $this->getBindArguments());
    }

    public function testInjectRegularParametersAndTailArgs(): void
    {
        $this->expectException(ArgumentBuilderExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot build arguments for.+ Does not accept unknown named parameter \$other_two\./');

        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->bindArguments(
            other: diGet('services.foo'),
            other_two: diGet('services.bar'),
            other_three: diGet('services.baz'),
        );
        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $ba->build();
    }

    public function testInjectRegularParameters(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux) => $quux;
        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

        self::assertEquals([0 => diGet(Quux::class)], $args);
    }

    public function testInjectVariadicParameters(): void
    {
        $fn = static fn (
            Baz $baz,
            #[Inject(Quux::class), Inject(QuuxTwo::class)]
            QuuxInterface ...$quux
        ) => $quux;

        $this->mockContainer->method('has')
            ->with(Baz::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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
            #[ProxyClosure(HeavyDependency::class),
                ProxyClosure(HeavyDependencyTwo::class),]
            Closure ...$heavyDependency,
        ): array => [($heavyDependency[0])()->doMake($quux), ($heavyDependency[1])()->doMake($quux)];

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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
            #[InjectByCallable('log')]
            callable ...$doCallable,
        ) => true;

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

        self::assertEquals(
            [
                0 => diGet(QuuxInterface::class),
                1 => diCallable(Baz::class.'::doMake'),
                2 => diCallable('log'),
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

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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

        $this->mockContainer->method('has')
            ->with(QuuxInterface::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $args = $ba->build();

        // argument order is important
        self::assertCount(1, $args);
        self::assertEquals(diGet(Quux::class), $args[0]);
    }

    public function testInjectToParameterWithDefaultValue(): void
    {
        $fn = static fn (?Bar $bar = null, #[Inject] ?BazInterface $baz = null, Foo ...$foo) => $baz;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $arg = $ba->build();

        self::assertCount(1, $arg);
        self::assertEquals(diGet(BazInterface::class), $arg[0]);
    }

    public function testParameterByTypeAndInjectToParameterWithDefaultValue(): void
    {
        $fn = static fn (
            Bar $bar,
            #[Inject]
            ?BazInterface $baz = null,
            Foo ...$foo
        ) => $baz;

        $this->mockContainer->method('has')
            ->with(Bar::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        $arg = $ba->build();

        self::assertCount(2, $arg);
        self::assertEquals(diGet(Bar::class), $arg[0]);
        self::assertEquals(diGet(BazInterface::class), $arg[1]);
    }

    public function testParameterResolvedByDiFactory(): void
    {
        $fn = static fn (
            #[DiFactory(BatFactory::class)]
            mixed $bat,
        ) => $bat;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);
        $arg = $ba->build();

        self::assertInstanceOf(DiDefinitionFactory::class, $arg[0]);
    }
}
