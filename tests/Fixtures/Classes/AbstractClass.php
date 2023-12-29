<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

abstract class AbstractClass
{
    public function hello(string $name): string
    {
        return "Hello {$name}";
    }
}
