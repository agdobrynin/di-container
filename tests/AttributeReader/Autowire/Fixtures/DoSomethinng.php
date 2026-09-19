<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
final class DoSomething {}
