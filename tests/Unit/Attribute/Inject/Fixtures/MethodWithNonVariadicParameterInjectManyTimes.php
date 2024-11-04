<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class MethodWithNonVariadicParameterInjectManyTimes
{
    public function __construct() {}

    public function rulesInvoke(
        #[Inject(RuleB::class, arguments: ['rule' => 'address'])]
        #[Inject(RuleA::class, arguments: ['rule' => 'zip'], isSingleton: true)]
        RuleInterface $rule,
    ): array {
        return [$rule];
    }
}
