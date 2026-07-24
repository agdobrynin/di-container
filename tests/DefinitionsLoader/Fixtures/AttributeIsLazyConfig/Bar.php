<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isLazy: true)]
final class Bar
{
    public function __construct(public readonly string $var = 'Lorem ipsim') {}
}
