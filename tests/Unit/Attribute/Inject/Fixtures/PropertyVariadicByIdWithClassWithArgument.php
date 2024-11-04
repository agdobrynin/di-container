<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class PropertyVariadicByIdWithClassWithArgument
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[Inject(RuleB::class, arguments: ['rule' => 'address'])]
        #[Inject(RuleA::class, arguments: ['rule' => 'zip'], isSingleton: true)]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
