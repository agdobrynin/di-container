<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\ParameterIterableVariadic;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleGenerator;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversNothing]
class VariadicArgumentTest extends TestCase
{
    public function testVariadicArgumentByName(): void
    {
        $definition = static function () {
            yield 'ruleC' => diAutowire(RuleC::class);

            yield diAutowire(RuleGenerator::class)
                ->bindArguments(
                    // имя аргумента в конструкторе
                    inputRule: diAutowire(RuleB::class),
                    inputRule2: diAutowire(RuleA::class),
                    inputRule3: diGet('ruleC'), // <-- получение по ссылке
                )
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definition())
            ->build()
        ;

        $ruleGenerator = $container->get(RuleGenerator::class);

        self::assertInstanceOf(RuleB::class, $ruleGenerator->getRules()['inputRule']);
        self::assertInstanceOf(RuleA::class, $ruleGenerator->getRules()['inputRule2']);
        self::assertInstanceOf(RuleC::class, $ruleGenerator->getRules()['inputRule3']);
    }

    public function testVariadicArgumentByIndex(): void
    {
        $definitions = static function () {
            yield 'ruleC' => diAutowire(RuleC::class);

            yield diAutowire(RuleGenerator::class)
                ->bindArguments(
                    diAutowire(RuleB::class),
                    diAutowire(RuleA::class),
                    diGet('ruleC'), // <-- получение по ссылке
                )
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $ruleGenerator = $container->get(RuleGenerator::class);

        self::assertCount(3, $ruleGenerator->getRules());
        self::assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
        self::assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[1]);
        self::assertInstanceOf(RuleC::class, $ruleGenerator->getRules()[2]);
    }

    public function testVariadicArgumentByNameForIterableParameter(): void
    {
        $definitions = static function () {
            yield diAutowire(ParameterIterableVariadic::class)
                ->bindArguments(parameter: ['first'])
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        self::assertEquals(['first'], $container->get(ParameterIterableVariadic::class)->getParameters()['parameter']);
    }

    public function testVariadicArgumentByIndexForIterableParameter(): void
    {
        $definitions = static function () {
            yield diAutowire(ParameterIterableVariadic::class, true)
                ->bindArguments(['first'], ['second'])
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        self::assertEquals(['first'], $container->get(ParameterIterableVariadic::class)->getParameters()[0]);
        self::assertEquals(['second'], $container->get(ParameterIterableVariadic::class)->getParameters()[1]);
    }

    public function testVariadicParameterWithoutBindArguments(): void
    {
        $container = (new DiContainerBuilder())->addDefinitions([
            diAutowire(RuleGenerator::class),
            RuleInterface::class => diAutowire(RuleA::class),
        ])
            ->build()
        ;

        $ruleGenerator = $container->get(RuleGenerator::class);

        self::assertEmpty($ruleGenerator->getRules());
    }
}
