<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

class ClassDependency
{
    public function __construct(public string $name) {}
}
