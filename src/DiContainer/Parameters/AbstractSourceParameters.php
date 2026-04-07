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
use function array_key_last;
use function array_keys;
use function count;
use function get_debug_type;
use function implode;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function ltrim;
use function preg_replace_callback;
use function sprintf;
use function str_contains;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
abstract class AbstractSourceParameters implements SourceParametersMutableInterface
{
    /**
     * @var array<non-empty-string, true>
     */
    private array $nameCircularCallWatcher = [];

    private bool $isParametersChanged = false;

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->internalParameters());
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

            if (false === $this->isParametersChanged && true === $this->internalParameters()[$name][0]) {
                return $this->internalParameters()[$name][1];
            }

            $resolvedValue = $this->resolveValue($this->internalParameters()[$name][1]);
            $this->internalParameters()[$name] = [true, $resolvedValue];
            $this->isParametersChanged = false;
        } finally {
            unset($this->nameCircularCallWatcher[$name]);
        }

        return $this->internalParameters()[$name][1];
    }

    public function set(string $name, mixed $value): void
    {
        if (isset($this->internalParameters()[$name])) {
            $this->isParametersChanged = true;
        }

        $this->internalParameters()[$name] = [false, $value];
    }

    public function add(iterable $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function parameters(): iterable
    {
        foreach ($this->internalParameters() as $name => [,$parameter]) {
            yield $name => $this->get($name);
        }
    }

    /**
     * @return SourceParameterType
     *
     * @throws ParameterNotFoundExceptionInterface
     * @throws ParameterExceptionInterface
     */
    protected function resolveValue(mixed $value): array|bool|float|int|string|UnitEnum|null
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
    protected function resolveString(string $value): string
    {
        if (!str_contains($value, '{')) {
            return $value;
        }

        /**
         * @param list<non-empty-string> $match
         */
        $replaceCallback = function (array $match): string {
            if (!isset($match[1])) {
                return '{';
            }

            /**
             * @var non-empty-string $placeHolder
             * @var non-empty-string $paramName
             */
            [$placeHolder, $paramName] = $match;

            $partValue = $this->get($paramName);

            if (!is_numeric($partValue) && !is_string($partValue)) {
                $resolvingName = array_key_last($this->nameCircularCallWatcher);

                throw new ParameterException(
                    ltrim(
                        sprintf('%s The parameter "%s": cannot concatenate value from parameter placeholder "%s" as type "%s" into string. A part value must be presents as number or string types.', $this->getCallStackNamesMessage(), $resolvingName, $placeHolder, get_debug_type($partValue))
                    )
                );
            }

            return (string) $partValue;
        };

        return (string) preg_replace_callback('/{([^{\s]+)}|{{/', $replaceCallback, $value);
    }

    protected function unsupportedValueType(mixed $value): ParameterException
    {
        $resolvingName = array_key_last($this->nameCircularCallWatcher);

        return new ParameterException(
            ltrim(
                sprintf('%s The parameter "%s" has unsupported value type: "%s". The parameter value can be scalar, enumerated, or null.', $this->getCallStackNamesMessage(), $resolvingName, get_debug_type($value))
            )
        );
    }

    protected function getCallStackNamesMessage(): string
    {
        return 1 < count($this->nameCircularCallWatcher)
            ? sprintf('Resolving parameters "%s".', implode('" -> "', array_keys($this->nameCircularCallWatcher)))
            : '';
    }

    /**
     * @return array<non-empty-string, array{0: bool, 1:SourceParameterType}>
     */
    abstract protected function &internalParameters(): array;
}
