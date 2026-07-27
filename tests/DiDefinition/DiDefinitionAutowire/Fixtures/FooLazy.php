<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

final class FooLazy
{
    public function __construct(private string $val = 'Lorem ipsum') {}

    public function getVal(): string
    {
        return $this->val;
    }
}
