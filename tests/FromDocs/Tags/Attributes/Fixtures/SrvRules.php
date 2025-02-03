<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class SrvRules
{
    public function __construct(
        #[TaggedAs(RuleInterface::class)]
        public iterable $rules
    ) {}
}
