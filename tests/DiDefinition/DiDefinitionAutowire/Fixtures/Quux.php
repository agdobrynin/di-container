<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

final class Quux
{
    public function __construct(RuleInterface ...$rule) {}
}
