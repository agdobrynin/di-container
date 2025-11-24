<?php

declare(strict_types=1);

namespace Tests\Tag;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Helper
 */
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
