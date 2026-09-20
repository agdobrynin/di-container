<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @internal
 *
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
final class PriorityBoundArguments
{
    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed> $arguments
     */
    public function __construct(public readonly array $arguments) {}
}
