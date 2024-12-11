<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

use Tests\FromDocs\PhpDefinitions\Fixtures\Variadic\RuleInterface;

class IterableArg
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(
        private iterable $rules
    ) {}

    /**
     * @return \Generator<RuleInterface>
     */
    public function getValues(): \Generator
    {
        yield from $this->rules;
    }
}
