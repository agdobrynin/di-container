<?php

declare(strict_types=1);

namespace Tests\Integration\ArgumentsViaAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Parameter;

#[Autowire(id: 'services.foo_bar', arguments: [
    new Autowire(
        Bar::class,
        arguments: [
            new Autowire(Bom::class, arguments: [new Parameter('params.foo')]),
        ]
    ),
])]
final class FooService
{
    public function __construct(
        public readonly QuxInterface $qux,
        #[Parameter]
        public readonly string $adminEmail,
    ) {}
}
