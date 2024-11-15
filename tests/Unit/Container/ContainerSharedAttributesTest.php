<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Attributes;
use Tests\Fixtures\Attributes\SimpleServiceSharedDefault;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\getParameterReflectionType
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

        $o = $c->get(Attributes\InjectSimpleArgumentWithSharedTrue::class)->arrayIterator();
        $o->append('🎈');

        $this->assertEquals(
            ['🥇', '🥉', '🎈'],
            \array_values((array) $c->get(Attributes\InjectSimpleArgumentWithSharedTrue::class)->arrayIterator())
        );
    }

    public function testSharedByAttributesFalse(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator(),
            $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator()
        );

        $r1 = $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator();
        $r2 = $c->get(Attributes\InjectSimpleArgumentWithSharedFalse::class)->arrayIterator();

        $this->assertEquals((array) $r1, (array) $r2);
    }

    public function testSharedByServiceAttributeDefault(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertNotSame(
            $c->get(Attributes\SimpleInterfaceSharedDefault::class),
            $c->get(Attributes\SimpleInterfaceSharedDefault::class)
        );

        $this->assertInstanceOf(
            SimpleServiceSharedDefault::class,
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

        $this->assertSame(
            $c->get(Attributes\SimpleInterfaceSharedTrue::class),
            $c->get(Attributes\SimpleInterfaceSharedTrue::class)
        );

        $this->assertInstanceOf(
            Attributes\SimpleServiceSharedTrue::class,
            $c->get(Attributes\SimpleInterfaceSharedTrue::class)
        );
    }

    public function testClassByDiFactoryIsSharedTrue(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->assertSame(
            $c->get(Attributes\FlyClass::class),
            $c->get(Attributes\FlyClass::class)
        );

        $this->assertInstanceOf(Attributes\FlyClass::class, $c->get(Attributes\FlyClass::class));
    }

    public function testResolveTowInterfacesWithDefaultSHared(): void
    {
        $c = (new DiContainerFactory())->make();
        $class = $c->get(Attributes\SimpleServiceWithTwoInterfacesDefault::class);

        $this->assertNotSame($class->service1, $class->service2);
        $this->assertInstanceOf(Attributes\SimpleInterfaceSharedDefault::class, $class->service1);
        $this->assertInstanceOf(Attributes\SimpleInterfaceSharedDefault::class, $class->service1);
        $this->assertInstanceOf(SimpleServiceSharedDefault::class, $class->service1);
        $this->assertInstanceOf(SimpleServiceSharedDefault::class, $class->service2);
    }

    public function testResolveInterfaceWithFiled(): void
    {
        $c = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $c->get(Attributes\SimpleServiceWithFailInject::class);
    }
}
