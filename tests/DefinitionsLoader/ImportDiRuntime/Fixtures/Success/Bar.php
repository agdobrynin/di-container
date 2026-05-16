<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Success;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime]
#[DiRuntime('services.bar')]
#[Tag('tags.bar_service')]
final class Bar {}
