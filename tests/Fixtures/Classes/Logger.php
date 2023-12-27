<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class Logger
{
    public function __construct(public string $name, public string $file) {}
}
