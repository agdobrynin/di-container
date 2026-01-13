<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
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
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(TaggedAs::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(Helper::class)]
#[CoversClass(LazyDefinitionIterator::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
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
