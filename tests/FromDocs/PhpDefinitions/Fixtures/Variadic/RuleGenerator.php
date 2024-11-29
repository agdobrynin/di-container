<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures\Variadic;

class RuleGenerator
{
    private iterable $rules;

    public function __construct(RuleInterface ...$inputRule)
    {
        $this->rules = $inputRule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
