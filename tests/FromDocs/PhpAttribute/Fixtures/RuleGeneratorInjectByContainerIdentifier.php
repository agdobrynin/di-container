<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class RuleGeneratorInjectByContainerIdentifier
{
    private iterable $rules;

    public function __construct(
        #[Inject('services.rules')]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
