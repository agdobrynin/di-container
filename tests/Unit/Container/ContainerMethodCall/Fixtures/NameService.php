<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class NameService
{
    public function __construct(public string $name) {}
}
