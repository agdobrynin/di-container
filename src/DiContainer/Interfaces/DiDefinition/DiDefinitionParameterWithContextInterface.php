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
 * Container parameter.
 *
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
interface DiDefinitionParameterWithContextInterface extends DiDefinitionInterface
{
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
