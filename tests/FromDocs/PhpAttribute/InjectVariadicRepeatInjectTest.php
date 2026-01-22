<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleA;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleGeneratorInjectRepeat;

/**
 * @internal
 */
#[CoversNothing]
class InjectVariadicRepeatInjectTest extends TestCase
{
    public function testInjectVariadicRepeatInject(): void
    {
        $container = (new DiContainerBuilder())->build();

        $ruleGenerator = $container->get(RuleGeneratorInjectRepeat::class);

        self::assertInstanceOf(RuleB::class, $ruleGenerator->getRules()[0]);
        self::assertInstanceOf(RuleA::class, $ruleGenerator->getRules()[1]);
    }
}
