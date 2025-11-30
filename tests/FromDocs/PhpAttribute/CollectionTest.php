<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\IterableArg;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleA;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleInterface;

use function func_get_args;
use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $definitions = [
            'services.rule-list' => diCallable(
                definition: static fn (RuleA $a, RuleB $b) => func_get_args(),
                isSingleton: true
            ),
        ];

        $container = new DiContainer(
            $definitions,
            new DiContainerConfig(
                useAttribute: true // use attributes
            )
        );

        $class = $container->get(IterableArg::class);

        foreach ($class->getValues() as $item) {
            $this->assertInstanceOf(RuleInterface::class, $item);
            $this->assertContains($item::class, [RuleA::class, RuleB::class]);
        }
    }
}
