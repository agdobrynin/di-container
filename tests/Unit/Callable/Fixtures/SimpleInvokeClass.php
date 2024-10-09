<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

class SimpleInvokeClass
{
    public function __construct(public string $name) {}

    public function __invoke(): string
    {
        return 'Hello '.$this->name.'!';
    }

    public function hello(): string
    {
        return $this->name.' hello!';
    }
}
