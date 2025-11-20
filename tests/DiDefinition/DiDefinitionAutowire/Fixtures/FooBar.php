<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

final class FooBar
{
    private RuleInterface $rule;

    #[SetupImmutable(
        new DiGet('services.secure_string'),
        rule: new DiGet('services.rule_a')
    )]
    public function method(string $bar, RuleInterface $rule): self
    {
        $new = clone $this;
        $new->rule = $rule;

        return $new;
    }
}
