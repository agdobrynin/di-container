<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerSharedAttributesTest extends TestCase
{
    public function testSharedByAttributesDefault(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\InjectSimpleArgument::class)->arrayIterator(),
            $c->get(Attributes\InjectSimpleArgument::class)->arrayIterator()
        );
    }

    public function testSharedByAttributesTrue(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertSame(
            $c->get(Attributes\InjectSimpleArgumentWithSharedTrue::class)->arrayIterator(),
            $c->get(Attributes\InjectSimpleArgumentWithSharedTrue::class)->arrayIterator()
        );
    }

    public function testSharedByAttributesFalse(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator(),
            $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator()
        );
    }
    public function testSharedByServiceAttributeDefault(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\SimpleInterfaceSharedDefault::class),
            $c->get(Attributes\SimpleInterfaceSharedDefault::class)
        );

        $this->assertInstanceOf(
            Attributes\SimpleServiceSharedDefault::class,
            $c->get(Attributes\SimpleInterfaceSharedDefault::class)
        );
    }

    public function testSharedByServiceAttributeFalse(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\SimpleInterfaceSharedFalse::class),
            $c->get(Attributes\SimpleInterfaceSharedFalse::class)
        );

        $this->assertInstanceOf(
            Attributes\SimpleServiceSharedFalse::class,
            $c->get(Attributes\SimpleInterfaceSharedFalse::class)
        );
    }

    public function testSharedByServiceAttributeTrue(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\SimpleInterfaceSharedTrue::class),
            $c->get(Attributes\SimpleInterfaceSharedTrue::class)
        );

        $this->assertInstanceOf(
            Attributes\SimpleServiceSharedTrue::class,
            $c->get(Attributes\SimpleInterfaceSharedTrue::class)
        );
    }
}
