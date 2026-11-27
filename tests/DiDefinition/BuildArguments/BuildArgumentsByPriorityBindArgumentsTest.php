<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use ArrayIterator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\Parameter;
use Kaspi\DiContainer\Attributes\ParameterRuntime;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterRuntime;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterWithContextAbstract;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DTO\AutowirePriorityBoundConfiguration;
use Kaspi\DiContainer\DTO\PriorityBoundArguments;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterRuntimeInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\FreezeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionFunction;
use Tests\DiDefinition\BuildArguments\Fixtures\Bar;
use Tests\DiDefinition\BuildArguments\Fixtures\Bat;
use Tests\DiDefinition\BuildArguments\Fixtures\BatFactory;
use Tests\DiDefinition\BuildArguments\Fixtures\Baz;
use Tests\DiDefinition\BuildArguments\Fixtures\Foo;
use Tests\DiDefinition\BuildArguments\Fixtures\HeavyDependency;
use Tests\DiDefinition\BuildArguments\Fixtures\Quux;
use Tests\DiDefinition\BuildArguments\Fixtures\QuuxInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diParameter;
use function Kaspi\DiContainer\diParameterRuntime;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(Helper::class)]
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(DiDefinitionParameter::class)]
#[CoversClass(DiDefinitionParameterRuntime::class)]
#[CoversClass(ParameterRuntime::class)]
#[CoversClass(DiDefinitionParameterWithContextAbstract::class)]
#[CoversFunction('Kaspi\DiContainer\diParameter')]
#[CoversFunction('Kaspi\DiContainer\diParameterRuntime')]
#[UsesClass(Autowire::class)]
#[UsesClass(DiFactory::class)]
#[UsesClass(DiDefinitionFactory::class)]
#[UsesClass(InjectByCallable::class)]
#[UsesClass(DiDefinitionCallable::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(ProxyClosure::class)]
#[UsesClass(DiDefinitionProxyClosure::class)]
#[UsesClass(TaggedAs::class)]
#[UsesClass(DiDefinitionTaggedAs::class)]
#[UsesClass(FreezeTrait::class)]
#[UsesClass(AutowirePriorityBoundConfiguration::class)]
#[UsesClass(PriorityBoundArguments::class)]
class BuildArgumentsByPriorityBindArgumentsTest extends TestCase
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

    public function testInjectRegularParametersPhpDefinitionHigherPriority(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux, #[Inject(Baz::class)] Foo $foo, Bar $bar) => $quux;

        $this->bindArguments(quux: diGet('services.quux'));

        $this->mockContainer->method('has')
            ->with(Bar::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
        $args = $ba->build();

        self::assertEquals(
            [
                0 => diGet('services.quux'),
                1 => diGet(Baz::class),
                2 => diGet(Bar::class),
            ],
            $args
        );
    }

    public function testReadAttributeFail(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux, #[Inject(Baz::class), Inject('service.one')] Foo $foo) => $quux;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        try {
            // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
            $args = $ba->build();
        } catch (ContainerExceptionInterface $e) {
            self::assertInstanceOf(ArgumentBuilderExceptionInterface::class, $e);
            self::assertStringContainsString('Cannot build argument via php attribute for Parameter #1', $e->getMessage());

            self::assertInstanceOf(AutowireExceptionInterface::class, $e->getPrevious());
            self::assertStringContainsString('can be applied once per non-variadic Parameter #1', $e->getPrevious()->getMessage());
        }
    }

    public function testParamOverrideHigherPriority(): void
    {
        $fn = static fn (#[Inject(Quux::class)] QuuxInterface $quux, #[Parameter('bar.one')] mixed $bar) => null;

        $this->bindArguments(bar: diParameter('bar.two'));

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
        $args = $ba->build();

        self::assertEquals(
            [
                0 => diGet(Quux::class),
                1 => diParameter('bar.two'),
            ],
            $args
        );
    }

    public function testAttributeParameterRuntime(): void
    {
        $fn = static fn (
            #[Parameter('foo')]
            string $str,
            #[ParameterRuntime]
            string $bar,
        ) => null;

        $this->bindArguments(str: diParameterRuntime('qux'));

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
        $args = $ba->build();

        self::assertCount(2, $args);
        self::assertInstanceOf(DiDefinitionParameterRuntimeInterface::class, $args[0]);
        self::assertEquals('qux', $args[0]->getDefinition());

        self::assertInstanceOf(DiDefinitionParameterRuntimeInterface::class, $args[1]);
        self::assertEquals('', $args[1]->getDefinition());
        self::assertEquals('bar', $args[1]->getContext());
    }

    public function testAttributeAutowireOnParameter(): void
    {
        $fn = static fn (#[Autowire('foo', isLazy: true)] ArrayIterator $a) => null;

        $this->bindArguments(diAutowire('qux'));

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        $args = $ba->build();

        self::assertCount(1, $args);
        self::assertEquals('qux', $args[0]->getIdentifier());
    }

    public function testTailArgs(): void
    {
        $fn = static fn () => null;
        $this->bindArguments(diAutowire('qux'));
        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        $args = $ba->build();

        self::assertCount(1, $args);
        self::assertEquals('qux', $args[0]->getIdentifier());
    }

    public function testTailArgsAsAttribute(): void
    {
        /*
         * For a container with the configuration $useAttribute = false,
         * all object will return as-is
         */
        $fn = static fn (QuuxInterface $quux) => $quux;

        $this->bindArguments(
            new Inject('services.foo'),
            new Autowire(Bat::class),
            new DiFactory(BatFactory::class),
            new InjectByCallable([Baz::class, 'doMake']),
            new ProxyClosure(HeavyDependency::class),
            new TaggedAs('tags'),
            new Parameter('param.foo'),
            new ParameterRuntime('param.qux'),
            new Inject('services.bar'),
        );

        $this->mockContainer->method('has')
            ->with(Bat::class)
            ->willReturn(true)
        ;

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer, true);

        $args = $ba->build();

        self::assertCount(9, $args);

        self::assertInstanceOf(DiDefinitionGet::class, $args[0]);
        self::assertEquals('services.foo', $args[0]->getDefinition());

        self::assertInstanceOf(DiDefinitionAutowire::class, $args[1]);
        self::assertEquals(Bat::class, $args[1]->getDefinition()->name);

        self::assertInstanceOf(DiDefinitionFactory::class, $args[2]);
        self::assertEquals([BatFactory::class, '__invoke'], $args[2]->getDefinition());

        self::assertInstanceOf(DiDefinitionCallable::class, $args[3]);
        self::assertEquals([Baz::class, 'doMake'], $args[3]->getDefinition());

        self::assertInstanceOf(DiDefinitionProxyClosure::class, $args[4]);
        self::assertEquals(HeavyDependency::class, $args[4]->getDefinition());

        self::assertInstanceOf(DiDefinitionTaggedAs::class, $args[5]);
        self::assertEquals('tags', $args[5]->getDefinition());

        self::assertInstanceOf(DiDefinitionParameter::class, $args[6]);
        self::assertEquals('param.foo', $args[6]->getDefinition());

        self::assertInstanceOf(DiDefinitionParameterRuntime::class, $args[7]);
        self::assertEquals('param.qux', $args[7]->getDefinition());

        self::assertInstanceOf(DiDefinitionGet::class, $args[8]);
        self::assertEquals('services.bar', $args[8]->getDefinition());
    }
}
