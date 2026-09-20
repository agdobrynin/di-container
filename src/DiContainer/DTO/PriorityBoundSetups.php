<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;

/**
 * @internal
 */
final class PriorityBoundSetups
{
    /**
     * @param null|array<non-empty-string, list<Setup|SetupImmutable>|Setup|SetupImmutable> $setups
     */
    public function __construct(public readonly ?array $setups) {}
}
