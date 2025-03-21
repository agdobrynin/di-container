<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\ParameterIterableVariadic;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleGenerator;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diValue
 *
 * @internal
 */
class VariadicArgumentTest extends TestCase
{
    public function testVariadicArgumentByName(): void
    {
        $definition = [
            'ruleC' => diAutowire(RuleC::class),
            diAutowire(RuleGenerator::class)
                ->bindArguments(
                    inputRule: // имя аргумента в конструкторе
                    [ // <-- обернуть параметры в массив для variadic типов если их несколько.
                        diAutowire(RuleB::class),
                        diAutowire(RuleA::class),
                        diGet('ruleC'), // <-- получение по ссылке
                    ], // <-- обернуть параметры в массив если их несколько.
                ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $ruleGenerator = $container->get(RuleGenerator::class);

        $this->assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
        $this->assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[1]);
        $this->assertInstanceOf(RuleC::class, $ruleGenerator->getRules()[2]);
    }

    public function testVariadicArgumentByIndex(): void
    {
        $definition = [
            'ruleC' => diAutowire(RuleC::class),
            diAutowire(RuleGenerator::class)
                ->bindArguments(
                    diAutowire(RuleB::class),
                    diAutowire(RuleA::class),
                    diGet('ruleC'), // <-- получение по ссылке
                ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $ruleGenerator = $container->get(RuleGenerator::class);

        $this->assertCount(3, $ruleGenerator->getRules());
        $this->assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
        $this->assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[1]);
        $this->assertInstanceOf(RuleC::class, $ruleGenerator->getRules()[2]);
    }

    public function testVariadicArgumentByNameForIterableParameter(): void
    {
        $definition = [
            diAutowire(ParameterIterableVariadic::class)
                ->bindArguments(parameter: diValue(['first'])),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $this->assertEquals(['first'], $container->get(ParameterIterableVariadic::class)->getParameters()[0]);
    }

    public function testVariadicArgumentByIndexForIterableParameter(): void
    {
        $definition = [
            diAutowire(ParameterIterableVariadic::class, true)
                ->bindArguments(['first'], ['second']),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $this->assertEquals(['first'], $container->get(ParameterIterableVariadic::class)->getParameters()[0]);
        $this->assertEquals(['second'], $container->get(ParameterIterableVariadic::class)->getParameters()[1]);
    }
}
