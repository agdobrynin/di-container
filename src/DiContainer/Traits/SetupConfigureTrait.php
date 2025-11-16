<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
trait SetupConfigureTrait
{
    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     * Immutable method mark as flag with value `true` in this array.
     *
     * @var array<non-empty-string, array<non-negative-int, array{0: bool, 1: array<int|string, mixed>}>>
     */
    private array $setup = [];

    /**
     * @param non-empty-string         $method
     * @param (DiDefinitionType|mixed) ...$argument
     */
    public function setup(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [false, $argument];

        return $this;
    }

    /**
     * @param non-empty-string         $method
     * @param (DiDefinitionType|mixed) ...$argument
     */
    public function setupImmutable(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [true, $argument];

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
