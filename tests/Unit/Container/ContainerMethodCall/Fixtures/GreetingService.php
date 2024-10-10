<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class GreetingService
{
    public function __construct(private string $greeting) {}

    public function getGreeting(): string
    {
        return $this->greeting;
    }
}
