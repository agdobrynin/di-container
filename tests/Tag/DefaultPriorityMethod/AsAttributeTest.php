<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedCollectionOne;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedCollectionTwo;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedFour;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedOne;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedThree;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedTwo;

use function Kaspi\DiContainer\diAutowire;

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
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 */
class AsAttributeTest extends TestCase
{
    public function testCollectionByInterface(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(TaggedFour::class),
            diAutowire(TaggedOne::class),
            diAutowire(TaggedThree::class),
            diAutowire(TaggedTwo::class),
        ]);

        $collection = $container->get(TaggedCollectionOne::class);

        $this->assertIsIterable($collection->items);

        $this->assertInstanceOf(TaggedTwo::class, $collection->items->current());
        $this->assertEquals('group1:101', $collection->items->current()->getTaggedInterfacePriority());
        $collection->items->next();
        $this->assertInstanceOf(TaggedFour::class, $collection->items->current());
        $this->assertEquals('group1:100', $collection->items->current()->getTaggedInterfacePriority());
        $collection->items->next();
        $this->assertInstanceOf(TaggedOne::class, $collection->items->current());
        $this->assertEquals('group1:1', $collection->items->current()->getTaggedInterfacePriority());
        $collection->items->next();
        $this->assertFalse($collection->items->valid());
    }

    public function testCollectionByInterfaceWithDefaultPriorityNotAllImplementMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(TaggedFour::class),
            diAutowire(TaggedOne::class),
            diAutowire(TaggedThree::class),
            diAutowire(TaggedTwo::class),
        ]);

        // priorityDefaultMethod: 'getOneOfPriority'
        $collection = $container->get(TaggedCollectionTwo::class);

        $this->assertIsIterable($collection->items);

        $this->assertInstanceOf(TaggedFour::class, $collection->items->current());
        $this->assertEquals(10_000, $collection->items->current()->getOneOfPriority());
        $collection->items->next();
        $this->assertInstanceOf(TaggedOne::class, $collection->items->current());
        $this->assertEquals(1_000, $collection->items->current()->getOneOfPriority());
        $collection->items->next();
        // class without priorityDefaultMethod: 'getOneOfPriority'
        $this->assertInstanceOf(TaggedTwo::class, $collection->items->current());
        $this->assertFalse((new \ReflectionClass($collection->items->current()))->hasMethod('getOneOfPriority'));

        $collection->items->next();
        $this->assertFalse($collection->items->valid());
    }
}
