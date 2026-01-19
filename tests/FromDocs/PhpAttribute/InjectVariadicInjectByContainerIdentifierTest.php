<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleGeneratorInjectByContainerIdentifier;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversNothing]
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

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions)
            ->build()
        ;

        $ruleGenerator = $container->get(RuleGeneratorInjectByContainerIdentifier::class);

        self::assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
    }
}
