<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

final class Foo
{
    private RuleInterface $rule;

    public function method(string $bar, RuleInterface $rule): self
    {
        $new = clone $this;
        $new->rule = $rule;

        return $new;
    }
}
