<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleGeneratorInjectByContainerIdentifier;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
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
