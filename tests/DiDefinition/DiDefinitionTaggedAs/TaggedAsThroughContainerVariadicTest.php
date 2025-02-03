<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerFactory;
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
 *
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 */
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
                ->bindArguments(variadic: [
                    diTaggedAs('tags.two'),
                    diTaggedAs('tags.one'),
                ]),
        ]);

        $class = $container->get(TaggedVariadicParameters::class);

        $this->assertCount(2, $class->variadic);
        // 'tags.two'
        $this->assertInstanceOf(TwoOne::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertInstanceOf(TwoTwo::class, $class->variadic[0]->current());
        $class->variadic[0]->next();
        $this->assertFalse($class->variadic[0]->valid());
        // 'tags.one'
        $this->assertInstanceOf(OneTwo::class, $class->variadic[1]->current()); // priority = 100
        $class->variadic[1]->next();
        $this->assertInstanceOf(OneOne::class, $class->variadic[1]->current()); // priority = 0
        $class->variadic[1]->next();
        $this->assertFalse($class->variadic[1]->valid());
    }

    public function testVariadicTaggedByInterfaceWithPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(OneOne::class),
            diAutowire(OneTwo::class),
            diAutowire(TwoOne::class),
            diAutowire(TwoTwo::class),
            diAutowire(TaggedVariadicParameters::class)
                ->bindArguments(variadic: [
                    diTaggedAs(OneInterface::class),
                    diTaggedAs(TwoInterface::class),
                ]),
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
