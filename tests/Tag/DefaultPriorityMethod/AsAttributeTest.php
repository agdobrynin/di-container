<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod;

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
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedCollectionOne;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedCollectionTwo;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedFour;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedOne;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedPriorityVsMethodPriority;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedThree;
use Tests\Tag\DefaultPriorityMethod\Fixtures\TaggedTwo;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(\Kaspi\DiContainer\Attributes\Tag::class)]
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
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
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
        $this->assertFalse((new ReflectionClass($collection->items->current()))->hasMethod('getOneOfPriority'));

        $collection->items->next();
        $this->assertFalse($collection->items->valid());
    }

    public function testPriorityVsMethodPriorityByAttributeTag(): void
    {
        $def = (new DiDefinitionAutowire(TaggedPriorityVsMethodPriority::class))
            ->bindTag(
                'tags.priority_vs_method_priority',
                options: ['priority.method' => 'getPriorityByPhpDefinition'],
                priority: 200
            ) // this binding tag overrider by php attribute on class
            ->setContainer((new DiContainerFactory())->make())
        ;

        // must return value defined in "priority" argument in `#[Tag]`
        self::assertEquals(10, $def->geTagPriority('tags.priority_vs_method_priority'));
    }

    public function testPriorityVsMethodPriorityByPhpDefinition(): void
    {
        $def = (new DiDefinitionAutowire(TaggedPriorityVsMethodPriority::class))
            ->bindTag(
                'tags.priority_vs_method_priority',
                options: ['priority.method' => 'getPriorityByPhpDefinition'],
                priority: 200
            ) // this binding tag overrider by php attribute on class
            ->setContainer(
                (new DiContainerFactory(
                    new DiContainerConfig(
                        useAttribute: false
                    )
                ))->make()
            )
        ;

        // must return value defined in "priority" set by `bingTag`
        // option "priority.method" must be ignored.
        self::assertEquals(200, $def->geTagPriority('tags.priority_vs_method_priority'));
    }
}
