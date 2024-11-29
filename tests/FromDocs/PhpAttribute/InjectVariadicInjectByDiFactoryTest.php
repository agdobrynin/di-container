<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleA;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleGeneratorInjectByDiFactory;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class InjectVariadicInjectByDiFactoryTest extends TestCase
{
    public function testInjectVariadicRepeatInject(): void
    {
        $container = (new DiContainerFactory())->make();

        $ruleGenerator = $container->get(RuleGeneratorInjectByDiFactory::class);

        $this->assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[0]);
        $this->assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[1]);
    }
}
