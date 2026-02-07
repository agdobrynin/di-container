<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\RemovedDefinitionIdsInterface;

final class RemovedDefinitionIds implements RemovedDefinitionIdsInterface
{
    public function __construct(private readonly DefinitionsConfiguratorInterface $definitionsConfigurator) {}

    public function getRemovedDefinitionIds(): array
    {
        return $this->definitionsConfigurator->getRemovedDefinitionIds();
    }
}
