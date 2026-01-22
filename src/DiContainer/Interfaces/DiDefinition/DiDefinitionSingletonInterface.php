<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionSingletonInterface extends DiDefinitionInterface
{
    public function isSingleton(): ?bool;
}
