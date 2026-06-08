<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

use function sprintf;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
trait BindArgumentsTrait
{
    use FreezeTrait;

    /**
     * User defined parameters by parameter name.
     *
     * @var BindArgumentsType
     */
    private array $bindArguments = [];

    public function bindArguments(mixed ...$argument): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::bindArguments() on a frozen definition.', static::class)
            );
        }

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
