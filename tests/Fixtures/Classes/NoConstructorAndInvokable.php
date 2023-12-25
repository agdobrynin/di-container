<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class NoConstructorAndInvokable
{
    public function __invoke(): string
    {
        return 'abc';
    }
}
