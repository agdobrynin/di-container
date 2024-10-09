<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class SimpleService
{
    public function __construct(public string $name) {}
}
