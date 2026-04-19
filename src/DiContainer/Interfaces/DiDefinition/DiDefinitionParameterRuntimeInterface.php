<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

/**
 * The container parameter must be set in the container at runtime.
 *
 * Any container parameter cannot be defined in configuration files,
 * because value parameter calculate at runtime using container dependencies.
 */
interface DiDefinitionParameterRuntimeInterface extends DiDefinitionParameterWithContextInterface
{
    /**
     * Additional message if the container parameter has not been defined yet.
     */
    public function getMessage(): string;
}
