<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DTO;

use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 *
 * @phpstan-type SetupConfigureArgumentsType array<non-empty-string|non-negative-int, DiDefinitionType|mixed>
 */
final class SetupTypeWithArguments
{
    /**
     * @param SetupConfigureArgumentsType $arguments
     */
    public function __construct(
        public readonly SetupConfigureMethod $setupType,
        public readonly array $arguments,
    ) {}
}
