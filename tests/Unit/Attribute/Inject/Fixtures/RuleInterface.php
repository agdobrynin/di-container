<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(RuleB::class)]
interface RuleInterface {}
