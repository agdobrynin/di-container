<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\OneInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\OneOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\OneTwo;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\TaggedVariadicParameters;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\TwoInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\TwoOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\TwoTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversFunction('Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Tag::class)]
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
class TaggedAsThroughContainerVariadicTest extends TestCase
{
    public function testVariadicByPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(OneOne::class)
                ->bindTag('tags.one'),
            diAutowire(OneTwo::class)
                ->bindTag('tags.one', ['priority' => 100]),
            diAutowire(TwoOne::class)
                ->bindTag('tags.two'),
            diAutowire(TwoTwo::class)
                ->bindTag('tags.two'),
            diAutowire(TaggedVariadicParameters::class)
                ->bindArguments(
                    variadic: diTaggedAs('tags.two'),
                    variadic2: diTaggedAs('tags.one'),
                ),
        ]);

        $class = $container->get(TaggedVariadicParameters::class);

        $this->assertCount(2, $class->variadic);
        // 'tags.two'
        $this->assertInstanceOf(TwoOne::class, $class->variadic['variadic']->current());
        $class->variadic['variadic']->next();
        $this->assertInstanceOf(TwoTwo::class, $class->variadic['variadic']->current());
        $class->variadic['variadic']->next();
        $this->assertFalse($class->variadic['variadic']->valid());
        // 'tags.one'
        $this->assertInstanceOf(OneTwo::class, $class->variadic['variadic2']->current()); // priority = 100
        $class->variadic['variadic2']->next();
        $this->assertInstanceOf(OneOne::class, $class->variadic['variadic2']->current()); // priority = 0
        $class->variadic['variadic2']->next();
        $this->assertFalse($class->variadic['variadic2']->valid());
    }

    public function testVariadicTaggedByInterfaceWithPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(OneOne::class),
            diAutowire(OneTwo::class),
            diAutowire(TwoOne::class),
            diAutowire(TwoTwo::class),
            diAutowire(TaggedVariadicParameters::class)
                ->bindArguments(
                    diTaggedAs(OneInterface::class),
                    diTaggedAs(TwoInterface::class),
                ),
        ]);

        $class = $container->get(TaggedVariadicParameters::class);

        $this->assertCount(2, $class->variadic);
        // tags as OneInterface
        $this->assertInstanceOf(OneInterface::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertInstanceOf(OneInterface::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertFalse($class->variadic[0]->valid());
        // tags as TwoInterface
        $this->assertInstanceOf(TwoInterface::class, $class->variadic[1]->current());
        $class->variadic[1]->next();
        $this->assertInstanceOf(TwoInterface::class, $class->variadic[1]->current());
        $class->variadic[1]->next();
        $this->assertFalse($class->variadic[0]->valid());
    }

    public function testVariadicTaggedByTagWithAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Variadic\Attributes\OneOne::class),
            diAutowire(Fixtures\Variadic\Attributes\OneTwo::class),
            diAutowire(Fixtures\Variadic\Attributes\TwoOne::class),
            diAutowire(Fixtures\Variadic\Attributes\TwoTwo::class),
        ]);

        $class = $container->get(Fixtures\Variadic\Attributes\TaggedByTagNameVariadicParameters::class);

        $this->assertCount(2, $class->variadic);
        // 'tags.two'
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoTwo::class, $class->variadic[0]->current()); // priority = 100
        $class->variadic[0]->next();
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoOne::class, $class->variadic[0]->current()); // priority = 0
        $class->variadic[0]->next();
        $this->assertFalse($class->variadic[0]->valid());
        // 'tags.one'
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneOne::class, $class->variadic[1]->current()); // priority = 0
        $class->variadic[1]->next();
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneTwo::class, $class->variadic[1]->current());
        $class->variadic[1]->next();
        $this->assertFalse($class->variadic[1]->valid());
    }

    public function testVariadicTaggedByInterfaceWithAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Variadic\Attributes\OneOne::class),
            diAutowire(Fixtures\Variadic\Attributes\OneTwo::class),
            diAutowire(Fixtures\Variadic\Attributes\TwoTwo::class),
            diAutowire(Fixtures\Variadic\Attributes\TwoOne::class),
        ]);

        $class = $container->get(Fixtures\Variadic\Attributes\TaggedByInterfaceNameVariadicParameters::class);

        $this->assertCount(2, $class->variadic);
        // 🚩 Priority not permit here, sorted as-is.
        // TwoInterface
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoInterface::class, $class->variadic[0]->current());
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoTwo::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoInterface::class, $class->variadic[0]->current());
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\TwoOne::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertFalse($class->variadic[0]->valid());
        // OneInterface
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneInterface::class, $class->variadic[1]->current());
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneOne::class, $class->variadic[1]->current());
        $class->variadic[1]->next();
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneInterface::class, $class->variadic[1]->current());
        $this->assertInstanceOf(Fixtures\Variadic\Attributes\OneTwo::class, $class->variadic[1]->current());
        $class->variadic[1]->next();
        $this->assertFalse($class->variadic[1]->valid());
    }
}
