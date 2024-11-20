<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\Variadic\RuleA;
use Tests\Unit\Definition\Fixtures\Variadic\RuleB;
use Tests\Unit\Definition\Fixtures\Variadic\RuleC;
use Tests\Unit\Definition\Fixtures\Variadic\RuleGenerator;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory::make
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 *
 * @internal
 */
class VariadicArgumentTest extends TestCase
{
    public function testVariadicArgumentWithDiAutowire(): void
    {
        $definition = [
            'ruleC' => diAutowire(RuleC::class),
            diAutowire(RuleGenerator::class)
                ->addArgument(
                    name: 'inputRule', // имя аргумента в конструкторе
                    value: [ // <-- обернуть параметры в массив для variadic типов
                        diAutowire(RuleB::class),
                        diAutowire(RuleA::class),
                        diReference('ruleC'), // <-- получение по ссылке
                    ], // <-- обернуть параметры в массив
                ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $class = $container->get(RuleGenerator::class);

        $this->assertInstanceOf(RuleB::class, $class->getRules()[0]);
        $this->assertInstanceOf(RuleA::class, $class->getRules()[1]);
        $this->assertInstanceOf(RuleC::class, $class->getRules()[2]);
    }

    public function testVariadicArgumentWithDiReference(): void
    {
        $definition = [
            'ruleC' => diAutowire(RuleC::class),
            diAutowire(
                RuleGenerator::class,
                [
                    // имя аргумента в конструкторе
                    'inputRule' => [ // <-- обернуть параметры в массив для variadic типов
                        diReference(RuleB::class), // <- при включённом zeroConfig сработает.
                        diReference(RuleA::class), // <- при включённом zeroConfig сработает.
                        diReference('ruleC'), // <-- получение по ссылке
                    ], // <-- обернуть параметры в массив
                ]
            ),
        ];

        $container = (new DiContainerFactory())->make($definition);

        $class = $container->get(RuleGenerator::class);

        $this->assertInstanceOf(RuleB::class, $class->getRules()[0]);
        $this->assertInstanceOf(RuleA::class, $class->getRules()[1]);
        $this->assertInstanceOf(RuleC::class, $class->getRules()[2]);
    }
}
