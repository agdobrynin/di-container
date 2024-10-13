<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

class DiFactoryOnPropertyFail
{
    public function __construct(#[DiFactory(Lorem::class)] public \ArrayIterator $iterator) {}
}
