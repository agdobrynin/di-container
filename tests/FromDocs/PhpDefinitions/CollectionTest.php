<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
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
#[CoversNothing]
class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $definitions = static function () {
            yield diAutowire(IterableArg::class, true)
                ->bindArguments(
                    rules: diGet('services.rule-list')
                )
            ;

            yield 'services.rule-list' => diCallable(
                definition: static fn (RuleA $a, RuleB $b, RuleC $c) => func_get_args(),
                isSingleton: true
            );
        };

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: false // Not use attributes
            )
        ))
            ->addDefinitions($definitions())
            ->build()
        ;

        $class = $container->get(IterableArg::class);

        self::assertSame($class, $container->get(IterableArg::class));

        foreach ($class->getValues() as $item) {
            self::assertInstanceOf(RuleInterface::class, $item);
            self::assertContains($item::class, [RuleA::class, RuleB::class, RuleC::class]);
        }

        self::assertSame($container->get('services.rule-list'), $container->get('services.rule-list'));
    }
}
