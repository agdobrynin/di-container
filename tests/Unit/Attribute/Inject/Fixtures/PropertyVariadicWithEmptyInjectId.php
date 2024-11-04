<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class PropertyVariadicWithEmptyInjectId
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[Inject]
        #[Inject]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
