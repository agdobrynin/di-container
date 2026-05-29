<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime('services.bar')]
final class Bar {}
