<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Generator;
use Kaspi\DiContainer\Attributes\Inject;

class IterableArg
{
    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(
        #[Inject('services.rule-list')]
        private iterable $rules
    ) {}

    /**
     * @return Generator<RuleInterface>
     */
    public function getValues(): Generator
    {
        yield from $this->rules;
    }
}
