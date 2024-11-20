<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class PropertyVariadicByIdWithClass
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[InjectContext(RuleB::class)]
        #[InjectContext(RuleA::class)]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
