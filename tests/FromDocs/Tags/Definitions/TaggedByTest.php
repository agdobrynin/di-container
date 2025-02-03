<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Tags\Definitions\Fixtures\One;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleA;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleB;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleC;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleInterface;
use Tests\FromDocs\Tags\Definitions\Fixtures\ServicesAnyArray;
use Tests\FromDocs\Tags\Definitions\Fixtures\ServicesAnyIterable;
use Tests\FromDocs\Tags\Definitions\Fixtures\SrvRules;
use Tests\FromDocs\Tags\Definitions\Fixtures\Two;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 */
class TaggedByTest extends TestCase
{
    public function testTaggedByInterface(): void
    {
        // Объявить классы
        $definitions = [
            diAutowire(RuleA::class),
            diAutowire(RuleB::class),
            diAutowire(RuleC::class),
            diAutowire(SrvRules::class)
                ->bindArguments(rules: diTaggedAs(RuleInterface::class)),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(SrvRules::class);
        // теперь в свойстве `rules` содержится итерируемая коллекция
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
            diAutowire(RuleA::class)
                ->bindTag(name: 'tags.rules', options: ['priority' => 10]),
            diAutowire(RuleB::class)
                ->bindTag(name: 'tags.other-rules', options: ['priority' => 20]),
            diAutowire(RuleC::class)
                ->bindTag(name: 'tags.rules', options: ['priority' => 100]),
            diAutowire(SrvRules::class)
                ->bindArguments(rules: diTaggedAs('tags.rules')),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(SrvRules::class);
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

    public function testTaggedLazyByName(): void
    {
        $definitions = [
            diAutowire(One::class)
                ->bindTag(name: 'tags.services-any'),
            diAutowire(Two::class)
                ->bindTag(name: 'tags.services-any'),
            diAutowire(RuleA::class),
            diAutowire(ServicesAnyIterable::class)
                ->bindArguments(services: diTaggedAs('tags.services-any')),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(ServicesAnyIterable::class);
        // теперь в свойстве `services` содержится итерируемая коллекция
        // из классов One, Two

        $this->assertTrue($class->services->valid());
        $this->assertInstanceOf(One::class, $class->services->current());
        $class->services->next();
        $this->assertInstanceOf(Two::class, $class->services->current());
        $class->services->next();
        $this->assertFalse($class->services->valid());
    }

    public function testTaggedNotLazyByName(): void
    {
        $definitions = [
            diAutowire(One::class)
                ->bindTag(name: 'tags.services-any'),
            diAutowire(Two::class)
                ->bindTag(name: 'tags.services-any'),
            diAutowire(RuleA::class),
            diAutowire(ServicesAnyArray::class)
                ->bindArguments(services: diTaggedAs('tags.services-any', false)),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(ServicesAnyArray::class);
        // теперь в свойстве `services` содержится массив
        // из классов One, Two

        $this->assertCount(2, $class->services);
        $this->assertInstanceOf(One::class, $class->services[0]);
        $this->assertInstanceOf(Two::class, $class->services[1]);
        $this->assertFalse(isset($class->services[2]));
    }
}
