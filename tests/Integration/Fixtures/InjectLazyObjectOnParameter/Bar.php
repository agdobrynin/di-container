<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\InjectLazyObjectOnParameter;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: ['Lorem ipsum'])]
final class Bar
{
    public function __construct(public readonly string $val) {}
}
