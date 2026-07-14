<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(resetter: [NonExist::class, 'reset'])]
final class FooFailResetter {}
