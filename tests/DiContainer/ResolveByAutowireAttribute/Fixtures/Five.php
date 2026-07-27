<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute\Fixtures;

final class Five
{
    public function __construct(private readonly Four $four) {}

    public function getFour(): Four
    {
        return $this->four;
    }
}
