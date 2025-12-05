<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
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
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
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
