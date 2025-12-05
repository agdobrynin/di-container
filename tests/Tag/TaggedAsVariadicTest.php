<?php

declare(strict_types=1);

namespace Tests\Tag;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(Helper::class)]
class TaggedAsVariadicTest extends TestCase
{
    public function testTaggedAsForVariadicByIndex(): void
    {
        $container = (new DiContainerFactory())->make([
            'key_1' => diCallable(static fn () => (object) ['rule1' => true])
                ->bindTag('tags.rules.group1', priority: 1),
            'key_2' => diCallable(static fn () => (object) ['rule2' => true])
                ->bindTag('tags.rules.group2', priority: 10),
            'key_3' => diCallable(static fn () => (object) ['rule3' => true])
                ->bindTag('tags.rules.group2', priority: 1),
            'key_4' => diCallable(static fn () => (object) ['rule4' => true])
                ->bindTag('tags.rules.group1', priority: 1),

            'variadic' => diCallable(static fn (array ...$rule) => $rule)
                ->bindArguments(
                    diTaggedAs('tags.rules.group2', isLazy: false, useKeys: false),
                    diTaggedAs('tags.rules.group1', isLazy: false, useKeys: false),
                ),
        ]);

        $res = $container->get('variadic');

        self::assertEquals([(object) ['rule2' => true], (object) ['rule3' => true]], $res[0]);
        self::assertEquals([(object) ['rule1' => true], (object) ['rule4' => true]], $res[1]);
    }

    public function testTaggedAsForVariadicByNamedArgument(): void
    {
        $container = (new DiContainerFactory())->make([
            'key_1' => diCallable(static fn () => (object) ['rule1' => true])
                ->bindTag('tags.rules.group1', priority: 1),
            'key_2' => diCallable(static fn () => (object) ['rule2' => true])
                ->bindTag('tags.rules.group2', priority: 10),
            'key_3' => diCallable(static fn () => (object) ['rule3' => true])
                ->bindTag('tags.rules.group2', priority: 1),
            'key_4' => diCallable(static fn () => (object) ['rule4' => true])
                ->bindTag('tags.rules.group1', priority: 1),

            'variadic' => diCallable(static fn (array ...$rule) => $rule)
                ->bindArguments(
                    rule: diTaggedAs('tags.rules.group2', isLazy: false, useKeys: false),
                    rule_2: diTaggedAs('tags.rules.group1', isLazy: false, useKeys: false),
                ),
        ]);

        $res = $container->get('variadic');

        self::assertEquals([(object) ['rule2' => true], (object) ['rule3' => true]], $res['rule']);
        self::assertEquals([(object) ['rule1' => true], (object) ['rule4' => true]], $res['rule_2']);
    }
}
