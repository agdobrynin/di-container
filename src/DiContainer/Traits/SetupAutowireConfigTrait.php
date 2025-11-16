<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;

trait SetupAutowireConfigTrait
{
    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     *
     * @var array<non-empty-string, array<non-negative-int, array{0: bool, array<int|string, mixed>}>>
     */
    private array $setup = [];

    public function setup(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [false, $argument];  // @phpstan-ignore assign.propertyType

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [true, $argument]; // @phpstan-ignore assign.propertyType

        return $this;
    }

    private function copySetupToDefinition(DiDefinitionSetupAutowireInterface $definition): void
    {
        foreach ($this->setup as $method => $setups) {
            foreach ($setups as $setup) {
                if (true === $setup[0]) {
                    $definition->setupImmutable($method, ...$setup[1]);
                } else {
                    $definition->setup($method, ...$setup[1]);
                }
            }
        }
    }
}
