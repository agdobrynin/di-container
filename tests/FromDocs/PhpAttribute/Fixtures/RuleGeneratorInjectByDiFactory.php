<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class RuleGeneratorInjectByDiFactory
{
    private iterable $rules;

    public function __construct(
        #[Inject(RulesDiFactory::class)]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
