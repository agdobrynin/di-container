<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByReference;

class PropertyNonVariadicReferenceInjectId
{
    public function __construct(
        #[InjectByReference('ruleA')]
        public RuleInterface $rule
    ) {}
}
