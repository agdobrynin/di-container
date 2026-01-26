<?php

declare(strict_types=1);

namespace Tests\DiContainer\GetDefinition\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service('foo')]
interface BatInterface {}
