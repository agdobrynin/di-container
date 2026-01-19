<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\LiteDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RulesWithSetter;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversNothing]
class SetupDefinitionTest extends TestCase
{
    public function testSetup(): void
    {
        $definitions = static function () {
            yield 'services.lite' => diAutowire(LiteDependency::class);

            yield diAutowire(RulesWithSetter::class, true)
                ->setup('addRule', rule: diGet(RuleB::class))
                ->setup('addRule', rule: diGet(RuleC::class))
                ->setup(
                    'addRule',
                    diGet('services.lite'),
                    diGet(RuleA::class)
                )
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        self::assertCount(3, $container->get(RulesWithSetter::class)->getRules());

        [$first, $second, $third] = $container->get(RulesWithSetter::class)->getRules();

        self::assertInstanceOf(RuleB::class, $first);
        self::assertInstanceOf(RuleC::class, $second);
        self::assertInstanceOf(RuleA::class, $third);
    }
}
