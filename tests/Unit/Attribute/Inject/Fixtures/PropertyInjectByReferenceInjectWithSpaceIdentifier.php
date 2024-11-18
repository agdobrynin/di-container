<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByReference;

class PropertyInjectByReferenceInjectWithSpaceIdentifier
{
    /**
     * @var RuleInterface[]
     */
    protected array $rules;

    public function __construct(
        #[InjectByReference('      ')]
        RuleInterface ...$rule
    ) {
        $this->rules = $rule;
    }
}
