<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class PrivateConstructorClass
{
    private function __construct(public string $name) {}
}
