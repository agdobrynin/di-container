<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DataFromDefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

final class DataFromDefinitionsConfigurator implements DataFromDefinitionsConfiguratorInterface
{
    public function __construct(private readonly DefinitionsConfiguratorInterface $definitionsConfigurator) {}

    public function getRemovedDefinitionIds(): array
    {
        return $this->definitionsConfigurator->getRemovedDefinitionIds();
    }

    public function getSetDefinitionIds(): array
    {
        return $this->definitionsConfigurator->getSetDefinitionIds();
    }
}
