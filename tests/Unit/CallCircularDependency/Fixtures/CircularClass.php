<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

class CircularClass
{
    public function __construct(CircularClassByInterface $circular) {}
}
