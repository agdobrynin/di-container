<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use ReflectionClass;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 *
 * @phpstan-type SetupConfigureArgumentsType array<non-empty-string|non-negative-int, DiDefinitionType|mixed>
 * @phpstan-type SetupConfigureItem array{0: SetupConfigureMethod, 1: SetupConfigureArgumentsType}
 */
trait SetupConfigureTrait
{
    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupConfigureItem>>
     */
    private array $setup = [];

    /**
     * Methods for setup service by PHP attribute via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupConfigureItem>>
     */
    private array $setupByAttributes;

    /**
     * @param non-empty-string            $method
     * @param SetupConfigureArgumentsType $arguments
     */
    public function setup(string $method, array $arguments = []): static
    {
        $this->setup[$method][] = [SetupConfigureMethod::Mutable, $arguments];

        return $this;
    }

    /**
     * @param non-empty-string            $method
     * @param SetupConfigureArgumentsType $arguments
     */
    public function setupImmutable(string $method, array $arguments): static
    {
        $this->setup[$method][] = [SetupConfigureMethod::Immutable, $arguments];

        return $this;
    }

    /**
     * @return array<non-empty-string, list<SetupConfigureItem>>
     */
    private function getSetups(ReflectionClass $class, DiContainerInterface $container): array
    {
        if (!$container->getConfig()->isUseAttribute()) {
            return $this->setup;
        }

        if (!isset($this->setupByAttributes)) {
            $this->setupByAttributes = [];

            foreach (AttributeReader::getSetupAttribute($class) as $setupAttr) {
                $this->setupByAttributes[$setupAttr->getIdentifier()][] = [
                    SetupConfigureMethod::fromAttribute($setupAttr), $setupAttr->getArguments(),
                ];
            }
        }

        return $this->setupByAttributes + $this->setup;
    }

    private function copySetupToDefinition(DiDefinitionSetupAutowireInterface $definition): void
    {
        foreach ($this->setup as $method => $setups) {
            foreach ($setups as $setup) {
                if (SetupConfigureMethod::Mutable === $setup[0]) {
                    $definition->setup($method, $setup[1]);
                } else {
                    $definition->setupImmutable($method, $setup[1]);
                }
            }
        }
    }
}
