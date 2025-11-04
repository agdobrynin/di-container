<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\LiteDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleA;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleB;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleC;
use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RulesWithSetter;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\ArgumentResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class SetupDefinitionTest extends TestCase
{
    public function testSetup(): void
    {
        $definitions = [
            'services.lite' => diAutowire(LiteDependency::class),
            diAutowire(RulesWithSetter::class, true)
                ->setup('addRule', rule: diGet(RuleB::class))
                ->setup('addRule', rule: diGet(RuleC::class))
                ->setup('addRule', diGet('services.lite'), diGet(RuleA::class)),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertCount(3, $container->get(RulesWithSetter::class)->getRules());

        [$first, $second, $third] = $container->get(RulesWithSetter::class)->getRules();

        $this->assertInstanceOf(RuleB::class, $first);
        $this->assertInstanceOf(RuleC::class, $second);
        $this->assertInstanceOf(RuleA::class, $third);
    }
}
