<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes;

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
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Tags\Attributes\Fixtures\GroupOne;
use Tests\FromDocs\Tags\Attributes\Fixtures\GroupTwo;
use Tests\FromDocs\Tags\Attributes\Fixtures\One;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleA;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleB;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleC;
use Tests\FromDocs\Tags\Attributes\Fixtures\SrvPriorityTagByMethodRules;
use Tests\FromDocs\Tags\Attributes\Fixtures\SrvPriorityTagRules;
use Tests\FromDocs\Tags\Attributes\Fixtures\SrvRules;
use Tests\FromDocs\Tags\Attributes\Fixtures\Three;
use Tests\FromDocs\Tags\Attributes\Fixtures\Two;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
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
#[CoversClass(SourceDefinitionsMutable::class)]
class TaggedByTest extends TestCase
{
    public function testTaggedByInterface(): void
    {
        // Объявить классы
        $definitions = [
            diAutowire(RuleA::class),
            diAutowire(RuleC::class),
            diAutowire(RuleB::class),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(SrvRules::class);
        // теперь в свойстве `rules` содержится итерируемая коллекция (\Generator)
        // из классов RuleA, RuleB - так как они имплементируют RuleInterface

        $this->assertIsIterable($class->rules);
        $this->assertInstanceOf(RuleA::class, $class->rules->current());
        $class->rules->next();
        $this->assertInstanceOf(RuleB::class, $class->rules->current());
        $class->rules->next();
        $this->assertFalse($class->rules->valid());
    }

    public function testTaggedPriority(): void
    {
        $definitions = [
            diAutowire(RuleA::class),
            diAutowire(RuleB::class),
            diAutowire(RuleC::class),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(SrvPriorityTagRules::class);
        // при получении коллекции отсортированные по приоритету
        // 1 - RuleC
        // 2 - RuleA
        $this->assertIsIterable($class->rules);
        $this->assertInstanceOf(RuleC::class, $class->rules->current());
        $this->assertEquals(RuleC::class, $class->rules->key()); // check key of tagged service

        $class->rules->next();

        $this->assertInstanceOf(RuleA::class, $class->rules->current());
        $this->assertEquals(RuleA::class, $class->rules->key());

        $class->rules->next();

        $this->assertFalse($class->rules->valid());
    }

    public function testTaggedByTagNameLazy(): void
    {
        $definitions = [
            diAutowire(One::class),
            diAutowire(Three::class),
            diAutowire(Two::class),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $classGroupTwo = $container->get(GroupTwo::class);
        // теперь в свойстве `services` содержится итерируемая коллекция
        // из классов Two, One - такой порядок обусловлен значением 'priority'
        $this->assertTrue($classGroupTwo->services->valid());
        $this->assertInstanceOf(Two::class, $classGroupTwo->services->current()); // priority = 1000
        $classGroupTwo->services->next();
        $this->assertInstanceOf(One::class, $classGroupTwo->services->current()); // default priority = 0

        $classGroupOne = $container->get(GroupOne::class);
        $this->assertCount(2, $classGroupOne->services);
        $this->assertInstanceOf(Three::class, $classGroupOne->services[Three::class]); // priority = 10
        $this->assertInstanceOf(One::class, $classGroupOne->services[One::class]); // default priority = 0
    }

    public function testTaggedByTagNameWithPriorityByMethod(): void
    {
        $definitions = [
            diAutowire(One::class),
            diAutowire(RuleA::class),
            diAutowire(RuleB::class),
            diAutowire(RuleC::class),
            diAutowire(Two::class),
            diAutowire(Three::class),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $srv = $container->get(SrvPriorityTagByMethodRules::class);

        $this->assertCount(3, $srv->rules);

        // access by key of tagged service
        $this->assertInstanceOf(RuleB::class, $srv->rules[RuleB::class]);
        $this->assertInstanceOf(RuleC::class, $srv->rules[RuleC::class]);
        $this->assertInstanceOf(RuleA::class, $srv->rules[RuleA::class]);
    }
}
