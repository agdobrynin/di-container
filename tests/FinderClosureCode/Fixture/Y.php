<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

class Y {
    public function getClosure(): \Closure
    {
        return static fn () => 'oka';
    }

    public static function make(): string
    {
        return 'yes';
    }
}
