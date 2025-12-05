<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\IterableArg;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleInterface;

use function func_get_args;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $definitions = [
            diAutowire(IterableArg::class, true)
                ->bindArguments(
                    rules: diGet('services.rule-list')
                ),
            'services.rule-list' => diCallable(
                definition: static fn (RuleA $a, RuleB $b, RuleC $c) => func_get_args(),
                isSingleton: true
            ),
        ];

        $container = new DiContainer(
            $definitions,
            new DiContainerConfig(
                useAttribute: false // Not use attributes
            )
        );

        $class = $container->get(IterableArg::class);

        $this->assertSame($class, $container->get(IterableArg::class));

        foreach ($class->getValues() as $item) {
            $this->assertInstanceOf(RuleInterface::class, $item);
            $this->assertContains($item::class, [RuleA::class, RuleB::class, RuleC::class]);
        }

        $this->assertSame($container->get('services.rule-list'), $container->get('services.rule-list'));
    }
}
