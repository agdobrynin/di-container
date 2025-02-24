<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

final class Two
{
    public function __construct(public string $param) {}

    public static function makeFromStatic(): Two
    {
        return new self('fromStatic');
    }
}
