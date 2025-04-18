<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleGeneratorInjectByContainerIdentifier;

use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class InjectVariadicInjectByContainerIdentifierTest extends TestCase
{
    public function testInjectVariadicRepeatInject(): void
    {
        $definitions = [
            'services.rules' => diCallable(
                // Автоматически внедрит зависимости этой callback функции
                static function (RuleB $b) {
                    return $b;
                }
            ),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $ruleGenerator = $container->get(RuleGeneratorInjectByContainerIdentifier::class);

        $this->assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
    }
}
