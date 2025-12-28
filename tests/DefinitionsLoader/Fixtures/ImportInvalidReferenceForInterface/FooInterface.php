<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportInvalidReferenceForInterface;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.foo')]
interface FooInterface {}
