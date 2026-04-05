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
interface DiDefinitionParameterInterface extends DiDefinitionInterface
{
    /**
     * Parameter name.
     *
     * @return non-empty-string
     */
    public function getDefinition(): string;

    /**
     * @return SourceParameterType
     *
     * @throws ParameterExceptionInterface
     * @throws ParameterNotFoundExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null;
}
