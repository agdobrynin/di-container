<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\BindArguments\Fixtures\Baz;
use Tests\Traits\BindArguments\Fixtures\Quux;
use Tests\Traits\BindArguments\Fixtures\QuuxInterface;
use Tests\Traits\BindArguments\Fixtures\QuuxTwo;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::checkVariadic
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getAttributeOnParameter
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getInjectAttribute
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
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

        $this->bindArguments(quux: diGet('services.quux'));

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [diGet(Quux::class)],
            $args
        );
    }

    public function testInjectRegularParametersPhpDefinitionHigherPriority(): void
    {
        $fn = static fn (#[Inject] Quux $quux) => $quux;

        $this->bindArguments(quux: diGet('services.quux'));

        $this->setContainer($this->containerMock);

        // Php attribute priority = false
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

    public function testInjectVaridicParameters(): void
    {
        $fn = static fn (
            Baz $baz,
            #[Inject(Quux::class), Inject(QuuxTwo::class)]
            QuuxInterface ...$quux
        ) => $quux;

        $this->setContainer($this->containerMock);

        // Php attribute priority = true
        $args = $this->buildArguments(new ReflectionFunction($fn), true);

        self::assertEquals(
            [
                0 => diGet(Baz::class),
                1 => diGet(Quux::class),
                2 => diGet(QuuxTwo::class),
            ],
            $args
        );
    }
}
