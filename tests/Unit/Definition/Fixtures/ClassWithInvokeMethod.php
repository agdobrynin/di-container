<?php

declare(strict_types=1);

namespace Tests\Unit\Definition\Fixtures;

class ClassWithInvokeMethod
{
    public function __construct(private SimpleService $simpleService) {}

    public function __invoke(WithoutConstructor $withoutConstructor): array
    {
        return [
            $withoutConstructor,
            $this->simpleService,
        ];
    }
}
