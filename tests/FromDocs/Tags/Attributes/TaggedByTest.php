<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
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
#[CoversNothing]
class TaggedByTest extends TestCase
{
    public function testTaggedByInterface(): void
    {
        // Объявить классы
        $definitions = static function () {
            yield diAutowire(RuleA::class);

            yield diAutowire(RuleC::class);

            yield diAutowire(RuleB::class);
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(SrvRules::class);
        // теперь в свойстве `rules` содержится итерируемая коллекция (\Generator)
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
            yield diAutowire(RuleA::class);

            yield diAutowire(RuleB::class);

            yield diAutowire(RuleC::class);
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $class = $container->get(SrvPriorityTagRules::class);
        // при получении коллекции отсортированные по приоритету
        // 1 - RuleC
        // 2 - RuleA
        self::assertIsIterable($class->rules);
        self::assertInstanceOf(RuleC::class, $class->rules->current());
        self::assertEquals(RuleC::class, $class->rules->key()); // check key of tagged service

        $class->rules->next();

        self::assertInstanceOf(RuleA::class, $class->rules->current());
        self::assertEquals(RuleA::class, $class->rules->key());

        $class->rules->next();

        self::assertFalse($class->rules->valid());
    }

    public function testTaggedByTagNameLazy(): void
    {
        $definitions = static function () {
            yield diAutowire(One::class);

            yield diAutowire(Three::class);

            yield diAutowire(Two::class);
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $classGroupTwo = $container->get(GroupTwo::class);
        // теперь в свойстве `services` содержится итерируемая коллекция
        // из классов Two, One - такой порядок обусловлен значением 'priority'
        self::assertTrue($classGroupTwo->services->valid());
        self::assertInstanceOf(Two::class, $classGroupTwo->services->current()); // priority = 1000

        $classGroupTwo->services->next();

        self::assertInstanceOf(One::class, $classGroupTwo->services->current()); // default priority = 0

        $classGroupOne = $container->get(GroupOne::class);

        self::assertCount(2, $classGroupOne->services);
        self::assertInstanceOf(Three::class, $classGroupOne->services[Three::class]); // priority = 10
        self::assertInstanceOf(One::class, $classGroupOne->services[One::class]); // default priority = 0
    }

    public function testTaggedByTagNameWithPriorityByMethod(): void
    {
        $definitions = static function () {
            yield diAutowire(One::class);

            yield diAutowire(RuleA::class);

            yield diAutowire(RuleB::class);

            yield diAutowire(RuleC::class);

            yield diAutowire(Two::class);

            yield diAutowire(Three::class);
        };

        $container = (new DiContainerBuilder())->addDefinitions($definitions())->build();
        $srv = $container->get(SrvPriorityTagByMethodRules::class);

        self::assertCount(3, $srv->rules);

        // access by key of tagged service
        self::assertInstanceOf(RuleB::class, $srv->rules[RuleB::class]);
        self::assertInstanceOf(RuleC::class, $srv->rules[RuleC::class]);
        self::assertInstanceOf(RuleA::class, $srv->rules[RuleA::class]);
    }
}
