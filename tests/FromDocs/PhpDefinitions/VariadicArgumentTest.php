<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleGenerator;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class VariadicArgumentTest extends TestCase
{
    public function testVariadicArgument(): void
    {
        $definition = [
            'ruleC' => diAutowire(RuleC::class),
            diAutowire(RuleGenerator::class)
                ->addArgument(
                    name: 'inputRule', // имя аргумента в конструкторе
                    value: [ // <-- обернуть параметры в массив для variadic типов если их несколько.
                        diAutowire(RuleB::class),
                        diAutowire(RuleA::class),
                        diReference('ruleC'), // <-- получение по ссылке
                    ], // <-- обернуть параметры в массив если их несколько.
                ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $ruleGenerator = $container->get(RuleGenerator::class);

        $this->assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
        $this->assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[1]);
        $this->assertInstanceOf(RuleC::class, $ruleGenerator->getRules()[2]);
    }
}
