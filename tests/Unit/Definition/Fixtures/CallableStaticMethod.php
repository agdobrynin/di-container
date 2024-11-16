<?php

declare(strict_types=1);

namespace Tests\Unit\Definition\Fixtures;

class CallableStaticMethod
{
    public static function myMethod(string $name, string $city): string
    {
        return "Hello {$name}! Welcome to {$city} 🎈";
    }
}
