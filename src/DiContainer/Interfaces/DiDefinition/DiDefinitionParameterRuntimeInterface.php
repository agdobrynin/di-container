<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use UnitEnum;

/**
 * The container parameter must be set in the container at runtime.
 *
 * Any container parameter cannot be defined in configuration files,
 * because value parameter calculate at runtime using container dependencies.
 *
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
interface DiDefinitionParameterRuntimeInterface extends DiDefinitionInterface
{
    /**
     * Additional message if the container parameter has not been defined yet.
     */
    public function getMessage(): string;

    /**
     * Parameter name.
     */
    public function getDefinition(): string;

    /**
     * Alternative container parameter name.
     */
    public function getContext(): ?string;

    /**
     * When the parameter name is not defined, $context will provide the container parameter name.
     */
    public function setContext(?string $context): static;

    /**
     * @return SourceParameterType
     *
     * @throws ParameterExceptionInterface
     * @throws ParameterNotFoundExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null;
}
