<?php

declare(strict_types=1);

namespace Tests\Unit\Container\CallCircularDependency\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(CircularClassByInject::class)]
interface CircularClassByInterfaceInject {}
