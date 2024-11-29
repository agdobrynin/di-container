<?php

declare(strict_types=1);

namespace Tests\Traits\CallableParser\Fixtures;

class SuperClass
{
    public function __construct(private string $service) {}

    public function __invoke(): string
    {
        return '🎈';
    }

    public function method(string $name): string
    {
        return $this->service.'🧶'.$name;
    }

    public static function staticMethod(): string
    {
        return 'ya';
    }
}
