<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Kaspi\DiContainer\Exception\ParameterCallCircularException;
use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Exception\ParameterNotFoundException;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use UnitEnum;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function get_debug_type;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function str_replace;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
final class SourceParameters implements SourceParametersMutableInterface
{
    /**
     * @var array<non-empty-string, array{0: bool, 1:SourceParameterType}>
     */
    private array $parameters = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $nameCircularCallWatcher = [];

    private bool $isParametersChanged = false;

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function get(string $name): array|bool|float|int|string|UnitEnum|null
    {
        if (!$this->has($name)) {
            throw new ParameterNotFoundException(name: $name);
        }

        try {
            if (isset($this->nameCircularCallWatcher[$name])) {
                throw new ParameterCallCircularException(names: [...array_keys($this->nameCircularCallWatcher), $name]);
            }

            $this->nameCircularCallWatcher[$name] = true;

            if (false === $this->isParametersChanged && true === $this->parameters[$name][0]) {
                return $this->parameters[$name][1];
            }

            $resolvedValue = $this->resolveValue($this->parameters[$name][1]);
            $this->parameters[$name] = [true, $resolvedValue];
            $this->isParametersChanged = false;
        } finally {
            unset($this->nameCircularCallWatcher[$name]);
        }

        return $this->parameters[$name][1];
    }

    public function remove(string $name): void
    {
        unset($this->parameters[$name]);
        $this->isParametersChanged = true;
    }

    public function set(string $name, mixed $value): void
    {
        $this->parameters[$name] = [false, $value];
        $this->isParametersChanged = true;
    }

    public function add(iterable $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function parameters(): iterable
    {
        foreach ($this->parameters as $name => [,$parameter]) {
            yield $name => $this->get($name);
        }
    }

    /**
     * @return SourceParameterType
     *
     * @throws ParameterNotFoundExceptionInterface
     * @throws ParameterExceptionInterface
     */
    private function resolveValue(mixed $value): array|bool|float|int|string|UnitEnum|null
    {
        if (is_string($value)) {
            return $this->resolveString($value);
        }

        if (is_array($value)) {
            $arrValue = [];

            foreach ($value as $k => $v) {
                if (!is_array($v) && !is_scalar($v) && null !== $v && !($v instanceof UnitEnum)) {
                    throw $this->unsupportedValueType($v);
                }

                $rK = is_string($k) ? $this->resolveString($k) : $k;
                $arrValue[$rK] = $this->resolveValue($v);
            }

            return $arrValue; // @phpstan-ignore return.type
        }

        if (!is_scalar($value) && null !== $value && !($value instanceof UnitEnum)) {
            throw $this->unsupportedValueType($value);
        }

        return $value;
    }

    /**
     * @param string $value parameter value
     *
     * @throws ParameterNotFoundExceptionInterface
     * @throws ParameterExceptionInterface
     */
    private function resolveString(string $value): string
    {
        if (!str_contains($value, '{')) {
            return $value;
        }

        $matches = [];
        preg_match_all('/{([^{\s]+)}|{{/', $value, $matches);

        foreach ($matches[1] as $index => $paramName) {
            if ('' === $paramName) {
                continue;
            }

            $partValue = $this->get($paramName);

            if (!is_numeric($partValue) && !is_string($partValue)) {
                throw new ParameterException(
                    sprintf('Cannot concatenate value from parameter "%s" as type "%s" into string. Supports a part value as number and string types.', $matches[0][$index], get_debug_type($partValue))
                );
            }

            $value = str_replace($matches[0][$index], (string) $partValue, $value);
        }

        return str_contains($value, '{{')
            ? str_replace('{{', '{', $value)
            : $value;
    }

    private function unsupportedValueType(mixed $value): ParameterException
    {
        $paramName = array_key_first($this->nameCircularCallWatcher);

        return new ParameterException(
            sprintf('The parameter "%s" has unsupported parameter value type: "%s". A parameter value supports a scalar, an enum or null types.', $paramName, get_debug_type($value))
        );
    }
}
