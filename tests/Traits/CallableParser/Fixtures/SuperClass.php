<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser\Fixtures;

class SuperClass
{
    public static function staticMethod(): string
    {
        return 'ya';
    }
}
