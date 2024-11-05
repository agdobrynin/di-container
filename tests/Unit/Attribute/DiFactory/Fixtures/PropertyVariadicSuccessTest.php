<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

class PropertyVariadicSuccessTest
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[DiFactory(RuleBDiFactory::class)]
        #[DiFactory(RuleADiFactory::class)]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
