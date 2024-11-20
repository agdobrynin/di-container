<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class MethodWithNonVariadicParameterInjectManyTimes
{
    public function __construct() {}

    public function rulesInvoke(
        #[InjectContext(RuleB::class, arguments: ['rule' => 'address'])]
        #[InjectContext(RuleA::class, arguments: ['rule' => 'zip'], isSingleton: true)]
        RuleInterface $rule,
    ): array {
        return [$rule];
    }
}
