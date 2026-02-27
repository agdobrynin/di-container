<?php

declare(strict_types=1);

namespace Tests\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire as DiAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

#[Autowire('service.multi_bar', isSingleton: true, arguments: [
    'qux' => new DiAutowire(Bar::class),
]),
    Autowire(arguments: [
        'qux' => new DiGet(Foo::class),
    ]),]
final class MultiAutowire
{
    public function __construct(public readonly QuxInterface $qux) {}
}
