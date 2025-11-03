<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @phpstan-import-type DiDefinitionArgumentType from DiDefinitionArgumentsInterface
 */
trait BindArgumentsTrait
{
    /**
     * User defined parameters by parameter name.
     *
     * @var array<non-empty-string|non-negative-int, DiDefinitionArgumentType>
     */
    private array $bindArguments = [];

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArguments = $argument; // @phpstan-ignore assign.propertyType

        return $this;
    }

    /**
     * @return array<non-empty-string|non-negative-int, DiDefinitionArgumentType>
     */
    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }
}
