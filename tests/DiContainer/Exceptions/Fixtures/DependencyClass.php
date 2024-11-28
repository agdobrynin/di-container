<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions\Fixtures;

class DependencyClass
{
    public function __construct(public string $value) {}
}
