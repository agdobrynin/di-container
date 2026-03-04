<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use UnitEnum;

interface SourceParametersMutableInterface
{
    /**
     * @param non-empty-string $name
     */
    public function has(string $name): bool;

    /**
     * @param non-empty-string $name
     *
     * @return null|array<null|scalar|UnitEnum>|scalar|UnitEnum
     *
     * @throws ParameterNotFoundExceptionInterface
     */
    public function get(string $name): array|bool|float|int|string|UnitEnum|null;

    /**
     * @param non-empty-string $name
     */
    public function remove(string $name): void;

    /**
     * @param non-empty-string                                 $name
     * @param null|array<null|scalar|UnitEnum>|scalar|UnitEnum $value
     */
    public function set(string $name, array|bool|float|int|string|UnitEnum|null $value): void;

    /**
     * @param array<non-empty-string, null|array<null|scalar|UnitEnum>|scalar|UnitEnum> $parameters
     */
    public function add(array $parameters): void;

    /**
     * @return array<non-empty-string, null|array<null|scalar|UnitEnum>|scalar|UnitEnum>
     */
    public function parameters(): array;
}
