<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportDiRuntime\Fixtures\Success;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
#[DiRuntime('services.bar')]
final class Bar {}
