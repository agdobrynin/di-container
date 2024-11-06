<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

trait ParametersForResolvingTrait
{
    /**
     * DiDefinition configured arguments.
     */
    private array $arguments = [];

    /**
     * Parameters from constructor or function defined in DiDefinition.
     *
     * @var \ReflectionParameter[]
     */
    private array $reflectedParameters = [];
    private array $parametersForResolving = [];

    public function getParametersForResolving(): array
    {
        if ([] === $this->parametersForResolving) {
            foreach ($this->reflectedParameters as $parameter) {
                if (!isset($this->arguments[$parameter->name])) {
                    $this->parametersForResolving[] = $parameter;

                    continue;
                }

                if (\is_array($this->arguments[$parameter->name]) && $parameter->isVariadic()) {
                    \array_push($this->parametersForResolving, ...$this->arguments[$parameter->name]);

                    continue;
                }

                $this->parametersForResolving[] = $this->arguments[$parameter->name];
            }
        }

        return $this->parametersForResolving;
    }
}
