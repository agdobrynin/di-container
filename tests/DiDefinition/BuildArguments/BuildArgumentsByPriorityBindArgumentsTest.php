<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionFunction;
use Tests\DiDefinition\BuildArguments\Fixtures\Bar;
use Tests\DiDefinition\BuildArguments\Fixtures\Baz;
use Tests\DiDefinition\BuildArguments\Fixtures\Foo;
use Tests\DiDefinition\BuildArguments\Fixtures\Quux;
use Tests\DiDefinition\BuildArguments\Fixtures\QuuxInterface;

use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(Helper::class)]
#[CoversClass(BindArgumentsTrait::class)]
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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
        $args = $ba->buildByPriorityBindArguments();

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

        $ba = new ArgumentBuilder($this->getBindArguments(), new ReflectionFunction($fn), $this->mockContainer);

        try {
            // 🚩 Use Php attribute and bind arguments - bind arguments highest priority.
            $args = $ba->buildByPriorityBindArguments();
        } catch (ContainerExceptionInterface $e) {
            self::assertInstanceOf(ArgumentBuilderExceptionInterface::class, $e);
            self::assertStringContainsString('Cannot build argument via php attribute for Parameter #1', $e->getMessage());

            self::assertInstanceOf(AutowireExceptionInterface::class, $e->getPrevious());
            self::assertStringContainsString('can be applied once per non-variadic Parameter #1', $e->getPrevious()->getMessage());
        }
    }
}
