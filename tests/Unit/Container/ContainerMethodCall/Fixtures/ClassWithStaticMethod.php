<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class ClassWithStaticMethod
{
    public function __invoke(string $greeting, string $name): string
    {
        return $greeting.' '.$name.'🕶';
    }

    public static function hello(string $greeting, string $name): string
    {
        return $greeting.' '.$name.'🎈';
    }
}
