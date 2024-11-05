<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassWithMethodPropertyVariadicSuccess
{
    public function __construct() {}

    public function getRules(
        #[DiFactory(RuleBDiFactory::class)]
        #[DiFactory(RuleADiFactory::class)]
        RuleInterface ...$rule
    ): array {
        return $rule;
    }
}
