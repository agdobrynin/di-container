<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassDepByAttributeWithInterfaceImplement;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDep;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDepAsArray;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeaveDepWithDependency;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
 *
 * @internal
 */
class TaggedAsImplementInterfaceTest extends TestCase
{
    public function testTaggedAsByAttributeImplementInterface(): void
    {
        $container = (new DiContainerFactory())
            ->make([
                diAutowire(AnyClass::class)
                    ->bindTag(HeavyDepInterface::class), // cannot retrieve because not implement HeavyDepInterface::class
                diAutowire(HeavyDepOne::class), // implement HeavyDepInterface::class
                diAutowire(ClassWithHeavyDepAsArray::class),
                diAutowire(HeavyDepTwo::class), // implement HeavyDepInterface::class
            ])
        ;

        $res = $container->get(ClassDepByAttributeWithInterfaceImplement::class)->getDep();

        $this->assertInstanceOf(HeavyDepOne::class, $res->current());
        $res->next();
        $this->assertInstanceOf(HeavyDepTwo::class, $res->current());
        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testTaggedAsImplementInterfaceByArgument(): void
    {
        $container = (new DiContainerFactory())
            ->make([
                diAutowire(AnyClass::class)
                    ->bindTag(HeavyDepInterface::class), // cannot retrieve because not implement HeavyDepInterface::class
                diAutowire(HeavyDepOne::class), // implement HeavyDepInterface::class
                diAutowire(ClassWithHeavyDepAsArray::class),
                diAutowire(HeavyDepTwo::class), // implement HeavyDepInterface::class,
                diAutowire(ClassWithHeavyDep::class)
                    ->bindArguments(diTaggedAs(HeavyDepInterface::class, false)),
            ])
        ;

        $res = $container->get(ClassWithHeavyDep::class)->getDep();

        $this->assertInstanceOf(HeavyDepOne::class, $res->current());
        $res->next();
        $this->assertInstanceOf(HeavyDepTwo::class, $res->current());
        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testTaggedAsByAttributeImplementInterfaceByWithExceptionWithLazy(): void
    {
        $container = (new DiContainerFactory())
            ->make([
                diAutowire(HeavyDepOne::class), // implement HeavyDepInterface::class
                diAutowire(HeaveDepWithDependency::class), // implement HeavyDepInterface::class with not registers dependency
            ])
        ;

        $res = $container->get(ClassDepByAttributeWithInterfaceImplement::class)->getDep();

        $this->assertInstanceOf(HeavyDepOne::class, $res->current());

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot build argument via type hint for Parameter #0 \[ <required> \$someDep ] in .+HeaveDepWithDependency::__construct\(\)\./');

        $res->next();
        $res->current();
    }

    public function testTaggedAsByArgumentWithExceptionWhenGetDefinition(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+ClassWithHeavyDep::__construct()/');

        $container = (new DiContainerFactory())
            ->make([
                diAutowire(HeavyDepOne::class), // implement HeavyDepInterface::class
                diAutowire(ClassWithHeavyDepAsArray::class),
                diAutowire(HeavyDepTwo::class), // implement HeavyDepInterface::class,
                diAutowire(ClassWithHeavyDep::class)
                    ->bindArguments(
                        diTaggedAs(HeavyDepInterface::class, false)
                    ),
                diAutowire('nonExistClass')
                    ->bindTag(HeavyDepInterface::class), // Cannot resolve class
            ])
        ;

        $container->get(ClassWithHeavyDep::class)->getDep();
    }
}
