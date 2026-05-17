<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Success;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime]
#[Tag('tags.foo_service')]
final class Foo {}
