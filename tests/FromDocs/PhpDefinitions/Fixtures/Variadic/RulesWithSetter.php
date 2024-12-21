<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures\Variadic;

use Tests\FromDocs\PhpDefinitions\Fixtures\LiteDependency;

class RulesWithSetter
{
    /**
     * @param RuleInterface[] $rules
     */
    private array $rules;

    public function addRule(LiteDependency $liteDependency, RuleInterface $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return RuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
