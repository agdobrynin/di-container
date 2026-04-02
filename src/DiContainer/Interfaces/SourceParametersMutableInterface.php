<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use UnitEnum;

/**
 * @phpstan-type SourceParameterType null|scalar|UnitEnum|(null|scalar|UnitEnum)[]
 */
interface SourceParametersMutableInterface
{
    /**
     * @param non-empty-string $name
     */
    public function has(string $name): bool;

    /**
     * @param non-empty-string $name
     *
     * @return SourceParameterType
     *
     * @throws ParameterExceptionInterface|ParameterNotFoundExceptionInterface
     */
    public function get(string $name): array|bool|float|int|string|UnitEnum|null;

    /**
     * @param non-empty-string $name
     */
    public function remove(string $name): void;

    /**
     * @param non-empty-string    $name
     * @param SourceParameterType $value
     */
    public function set(string $name, array|bool|float|int|string|UnitEnum|null $value): void;

    /**
     * @param iterable<non-empty-string, SourceParameterType> $parameters
     */
    public function add(iterable $parameters): void;

    /**
     * @return iterable<non-empty-string, SourceParameterType>
     *
     * @throws ParameterExceptionInterface|ParameterNotFoundExceptionInterface
     */
    public function parameters(): iterable;
}
