<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeInject\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(RuleB::class)]
interface RuleInterface {}
