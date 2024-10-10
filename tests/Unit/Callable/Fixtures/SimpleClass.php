<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

class SimpleClass
{
    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }
}
