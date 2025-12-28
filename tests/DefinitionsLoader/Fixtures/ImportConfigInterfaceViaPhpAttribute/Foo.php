<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportConfigInterfaceViaPhpAttribute;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire]
#[Autowire(id: 'services.foo')]
final class Foo implements FooInterface {}
