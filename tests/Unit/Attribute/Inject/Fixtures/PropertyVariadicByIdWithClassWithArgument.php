<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class PropertyVariadicByIdWithClassWithArgument
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[InjectContext(RuleB::class, arguments: ['rule' => 'address'])]
        #[InjectContext(RuleA::class, arguments: ['rule' => 'zip'], isSingleton: true)]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
