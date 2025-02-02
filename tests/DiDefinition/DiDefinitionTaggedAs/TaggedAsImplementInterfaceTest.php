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
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
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
        $this->expectExceptionMessage('Unresolvable dependency');

        $res->next();
    }

    public function testTaggedAsByArgumentWithExceptionWhenGetDefinition(): void
    {
        $container = (new DiContainerFactory())
            ->make([
                diAutowire(HeavyDepOne::class), // implement HeavyDepInterface::class
                diAutowire(ClassWithHeavyDepAsArray::class),
                diAutowire(HeavyDepTwo::class), // implement HeavyDepInterface::class,
                diAutowire(ClassWithHeavyDep::class)
                    ->bindArguments(diTaggedAs(HeavyDepInterface::class, false)),
                diAutowire('nonExistClass')
                    ->bindTag(HeavyDepInterface::class), // Cannot resolve class
            ])
        ;

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Class "nonExistClass" does not exist');

        $container->get(ClassWithHeavyDep::class)->getDep();
    }
}
