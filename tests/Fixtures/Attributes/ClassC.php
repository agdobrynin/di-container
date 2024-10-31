<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class ClassC
{
    public function __construct(#[Inject] public array|ClassB $var) {}
}
