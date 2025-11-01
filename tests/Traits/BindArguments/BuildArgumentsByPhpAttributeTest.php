<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments;

use Closure;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\BindArguments\Fixtures\Baz;
use Tests\Traits\BindArguments\Fixtures\HeavyDependency;
use Tests\Traits\BindArguments\Fixtures\HeavyDependencyTwo;
use Tests\Traits\BindArguments\Fixtures\Quux;
use Tests\Traits\BindArguments\Fixtures\QuuxInterface;
use Tests\Traits\BindArguments\Fixtures\QuuxTwo;

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
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::checkVariadic
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getAttributeOnParameter
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getInjectAttribute
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getInjectByCallableAttribute
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getProxyClosureAttribute
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getTaggedAsAttribute
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class BuildArgumentsByPhpAttributeTest extends TestCase
{
    use BindArgumentsTrait;
    use DiContainerTrait;
    use AttributeReaderTrait;
    private DiContainerInterface $containerMock;

    public function setUp(): void
    {
        $this->bindArguments();
        $this->containerMock = $this->createMock(DiContainerInterface::class);
        $this->containerMock->method('getConfig')->willReturn(
            new DiContainerConfig(useAttribute: true)
        );
    }

    public function testInjectRegularParametersAttributeHigherPriority(): void
    {
        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->setContainer($this->containerMock);
        $this->bindArguments(quux: diGet('services.quux'));

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [0 => diGet(Quux::class)],
            $args
        );

        self::assertEquals(
            ['quux' => diGet('services.quux')],
            $this->getBindArguments(),
        );
    }

    public function testInjectRegularParametersAndTailArgs(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Does not accept unknown named parameter $other_two');

        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->setContainer($this->containerMock);
        $this->bindArguments(
            other: diGet('services.foo'),
            other_two: diGet('services.bar'),
            other_three: diGet('services.baz'),
        );

        // Php attribute priority = true
        $this->buildArguments(new ReflectionFunction($fn), true);
    }

    public function testInjectRegularParametersPhpDefinitionHigherPriority(): void
    {
        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->setContainer($this->containerMock);
        $this->bindArguments(quux: diGet('services.quux'));

        // Use of attributes is enabled in container configuration.
        // Php attribute priority by argument $isAttributeOnParamHigherPriority = false
        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        self::assertEquals(
            ['quux' => diGet('services.quux')],
            $args
        );
    }

    public function testInjectRegularParameters(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux) => $quux;

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [0 => diGet(Quux::class)],
            $args
        );
    }

    public function testInjectVariadicParameters(): void
    {
        $fn = static fn (
            Baz $baz,
            #[Inject(Quux::class), Inject(QuuxTwo::class)]
            QuuxInterface ...$quux
        ) => $quux;

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diProxyClosure(HeavyDependency::class),
                diGet(QuuxInterface::class),
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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diGet(QuuxInterface::class),
                diProxyClosure(HeavyDependency::class),
                diProxyClosure(HeavyDependencyTwo::class),
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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diGet(QuuxInterface::class),
                diCallable(Baz::class.'::doMake'),
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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diGet(QuuxInterface::class),
                diCallable(Baz::class.'::doMake'),
                diCallable('App\Helpers\fn\funcUser'),
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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diGet(QuuxInterface::class),
                diTaggedAs('tags.validate_string'),
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

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                diGet(QuuxInterface::class),
                diTaggedAs('tags.validator_string'),
                diTaggedAs('tags.validator_password', priorityDefaultMethod: 'self::getPriority'),
            ],
            $args,
        );
    }
}
