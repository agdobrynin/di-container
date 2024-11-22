<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

class CallableArgument
{
    public function __construct() {}

    public function __invoke(string $name): string
    {
        return "{$name} 😀";
    }
}
