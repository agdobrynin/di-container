<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(FirstClass::class)]
interface CircularClassByInterfaceInject {}
