<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

class RuleA implements RuleInterface
{
    public function __construct(public string $rule = 'trim') {}
}
