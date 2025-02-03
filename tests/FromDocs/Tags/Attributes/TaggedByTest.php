<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Tags\Attributes\Fixtures\GroupOne;
use Tests\FromDocs\Tags\Attributes\Fixtures\GroupTwo;
use Tests\FromDocs\Tags\Attributes\Fixtures\One;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleA;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleB;
use Tests\FromDocs\Tags\Attributes\Fixtures\RuleC;
use Tests\FromDocs\Tags\Attributes\Fixtures\SrvPriorityTagRules;
use Tests\FromDocs\Tags\Attributes\Fixtures\SrvRules;
use Tests\FromDocs\Tags\Attributes\Fixtures\Three;
use Tests\FromDocs\Tags\Attributes\Fixtures\Two;

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
 */
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
        $class->rules->next();
        $this->assertInstanceOf(RuleA::class, $class->rules->current());
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
        $this->assertInstanceOf(Three::class, $classGroupOne->services[0]); // priority = 10
        $this->assertInstanceOf(One::class, $classGroupOne->services[1]); // default priority = 0
    }
}
