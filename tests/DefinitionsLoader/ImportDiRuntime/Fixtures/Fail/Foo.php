<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Fail;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiRuntime;

#[Autowire('services.foo_autowire')]
#[DiRuntime('services.foo_runtime')]
final class Foo {}
