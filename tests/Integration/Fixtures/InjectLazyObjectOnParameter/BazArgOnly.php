<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\InjectLazyObjectOnParameter;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: ['Dol uni'])]
final class BazArgOnly implements ServiceInterface
{
    public function __construct(public readonly string $val) {}
}
