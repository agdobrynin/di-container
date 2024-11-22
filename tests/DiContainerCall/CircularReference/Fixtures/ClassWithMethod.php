<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\CircularReference\Fixtures;

class ClassWithMethod
{
    public function method(
        $service
    ): void {}
}
