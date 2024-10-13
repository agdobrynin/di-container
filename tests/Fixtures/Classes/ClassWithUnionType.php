<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class ClassWithUnionType
{
    public function __construct(\ReflectionClass|\ReflectionMethod $dependency) {}
}
