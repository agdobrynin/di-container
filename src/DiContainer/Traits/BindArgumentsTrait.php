<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait BindArgumentsTrait
{
    /**
     * User defined parameters by parameter name.
     *
     * @var array<int|string, mixed>
     */
    private array $bindArguments = [];

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function addArgument(int|string $name, mixed $value): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);

        $this->bindArguments[$name] = $value;

        return $this;
    }

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function addArguments(array $arguments): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);
        $this->bindArguments = $arguments;

        return $this;
    }

    /**
     * @phan-suppress PhanTypeMismatchReturn
     */
    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArguments = $argument;

        return $this;
    }

    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }
}
