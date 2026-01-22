<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Tags\Definitions\Fixtures\One;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleA;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleB;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleC;
use Tests\FromDocs\Tags\Definitions\Fixtures\RuleInterface;
use Tests\FromDocs\Tags\Definitions\Fixtures\ServicesAnyArray;
use Tests\FromDocs\Tags\Definitions\Fixtures\ServicesAnyIterable;
use Tests\FromDocs\Tags\Definitions\Fixtures\SrvRules;
use Tests\FromDocs\Tags\Definitions\Fixtures\SrvRulesPriorityByMethod;
use Tests\FromDocs\Tags\Definitions\Fixtures\Two;

use function current;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;
use function next;

/**
 * @internal
 */
#[CoversNothing]
class TaggedByTest extends TestCase
{
    public function testTaggedByInterface(): void
    {
        // Объявить классы
        $definitions = static function () {
            yield diAutowire(RuleA::class);

            yield diAutowire(RuleB::class);

            yield diAutowire(RuleC::class);

            yield diAutowire(SrvRules::class)
                ->bindArguments(rules: diTaggedAs(RuleInterface::class))
            ;
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(SrvRules::class);
        // теперь в свойстве `rules` содержится итерируемая коллекция
        // из классов RuleA, RuleB - так как они имплементируют RuleInterface
        self::assertIsIterable($class->rules);
        self::assertInstanceOf(RuleA::class, $class->rules->current());

        $class->rules->next();

        self::assertInstanceOf(RuleB::class, $class->rules->current());

        $class->rules->next();

        self::assertFalse($class->rules->valid());
    }

    public function testTaggedPriority(): void
    {
        $definitions = static function () {
            yield diAutowire(RuleA::class)
                ->bindTag(name: 'tags.rules', options: ['priority' => 10])
            ;

            yield diAutowire(RuleB::class)
                ->bindTag(name: 'tags.other-rules', priority: 20)
            ;

            yield diAutowire(RuleC::class)
                ->bindTag(name: 'tags.rules', options: ['priority' => 100])
            ;

            yield diAutowire(SrvRules::class)
                ->bindArguments(rules: diTaggedAs('tags.rules'))
            ;
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(SrvRules::class);
        // при получении коллекции отсортированные по приоритету
        // 1 - RuleC
        // 2 - RuleA
        self::assertIsIterable($class->rules);
        self::assertInstanceOf(RuleC::class, $class->rules->current());

        $class->rules->next();

        self::assertInstanceOf(RuleA::class, $class->rules->current());

        $class->rules->next();

        self::assertFalse($class->rules->valid());
    }

    public function testTaggedLazyByName(): void
    {
        $definitions = static function () {
            yield diAutowire(One::class)
                ->bindTag(name: 'tags.services-any')
            ;

            yield diAutowire(Two::class)
                ->bindTag(name: 'tags.services-any')
            ;

            yield diAutowire(RuleA::class);

            yield diAutowire(ServicesAnyIterable::class)
                ->bindArguments(services: diTaggedAs('tags.services-any'))
            ;
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(ServicesAnyIterable::class);
        // теперь в свойстве `services` содержится итерируемая коллекция
        // из классов One, Two

        self::assertTrue($class->services->valid());
        self::assertInstanceOf(One::class, $class->services->current());

        $class->services->next();

        self::assertInstanceOf(Two::class, $class->services->current());

        $class->services->next();

        self::assertFalse($class->services->valid());
    }

    public function testTaggedNotLazyByName(): void
    {
        $definitions = static function () {
            yield diAutowire(One::class)
                ->bindTag(name: 'tags.services-any')
            ;

            yield diAutowire(Two::class)
                ->bindTag(name: 'tags.services-any')
            ;

            yield diAutowire(RuleA::class);

            yield diAutowire(ServicesAnyArray::class)
                ->bindArguments(services: diTaggedAs('tags.services-any', false))
            ;
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(ServicesAnyArray::class);
        // теперь в свойстве `services` содержится массив
        // из классов One, Two

        self::assertCount(2, $class->services);
        self::assertInstanceOf(One::class, current($class->services));
        self::assertInstanceOf(Two::class, next($class->services));
        self::assertFalse(next($class->services));
    }

    public function testTaggedByTagWithPriorityByMethod(): void
    {
        $definitions = static function () {
            yield diAutowire(SrvRulesPriorityByMethod::class)
                ->bindArguments(
                    rules: diTaggedAs(
                        'tags.rules',
                        false, // 🚩 get services as array
                        priorityDefaultMethod: 'getCollectionPriority'
                    )
                )
            ;

            yield diAutowire(One::class);

            yield diAutowire(RuleA::class)
                ->bindTag(name: 'tags.rules', options: ['priority.method' => 'getPriority'])
            ;

            yield diAutowire(RuleB::class)
                ->bindTag(name: 'tags.rules', options: ['priority.method' => 'getPriorityOther'])
            ;

            yield diAutowire(RuleC::class)
                ->bindTag(name: 'tags.rules')
            ;

            yield diAutowire(Two::class);
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $srv = $container->get(SrvRulesPriorityByMethod::class);

        self::assertCount(3, $srv->rules);

        self::assertInstanceOf(RuleB::class, current($srv->rules));
        self::assertInstanceOf(RuleC::class, next($srv->rules));
        self::assertInstanceOf(RuleA::class, next($srv->rules));
        self::assertFalse(next($srv->rules));
    }
}
