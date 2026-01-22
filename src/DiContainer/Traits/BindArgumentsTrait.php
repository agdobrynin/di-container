<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
trait BindArgumentsTrait
{
    /**
     * User defined parameters by parameter name.
     *
     * @var BindArgumentsType
     */
    private array $bindArguments = [];

    public function bindArguments(mixed ...$argument): static
    {
        /**
         * @phpstan-var BindArgumentsType $argument
         */
        $this->bindArguments = $argument;

        return $this;
    }

    /**
     * @return BindArgumentsType
     */
    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }
}
