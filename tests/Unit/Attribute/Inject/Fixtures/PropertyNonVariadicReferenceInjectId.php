<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class PropertyNonVariadicReferenceInjectId
{
    public function __construct(
        #[Inject('@ruleA')]
        public RuleInterface $rule
    ) {}
}
