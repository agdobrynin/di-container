<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportConfigInterface;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.foo')]
interface FooInterface {}
