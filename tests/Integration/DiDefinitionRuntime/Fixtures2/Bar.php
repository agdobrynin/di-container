<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures2;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('foo.attr')]
final class Bar {}
