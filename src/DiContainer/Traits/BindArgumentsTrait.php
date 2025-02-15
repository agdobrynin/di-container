<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait BindArgumentsTrait
{
    /**
     * User defined parameters by parameter name.
     *
     * @var array<non-empty-string|non-negative-int, mixed>
     */
    private array $bindArguments = [];

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     */
    public function addArgument(int|string $name, mixed $value): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);

        $this->bindArguments[$name] = $value;

        return $this;
    }

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     */
    public function addArguments(array $arguments): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);
        $this->bindArguments = $arguments;

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArguments = $argument; // @phpstan-ignore assign.propertyType

        return $this;
    }

    /**
     * @return array<non-empty-string|non-negative-int, mixed>
     */
    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }
}
