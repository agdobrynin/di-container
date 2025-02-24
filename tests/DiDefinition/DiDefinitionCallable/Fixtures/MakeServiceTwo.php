<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

final class MakeServiceTwo
{
    public function __construct(private string $def) {}

    public function __invoke(): Two
    {
        return new Two($this->def);
    }
}
