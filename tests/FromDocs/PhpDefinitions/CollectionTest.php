<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
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
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
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
