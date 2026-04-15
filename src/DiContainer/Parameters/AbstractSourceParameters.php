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
use function array_key_last;
use function array_keys;
use function count;
use function get_debug_type;
use function implode;
use function is_array;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function str_contains;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 *
 * @phpstan-type SourceParameterResolvedType array{0: true, SourceParameterType}
 * @phpstan-type SourceParameterRawType array{0: false, mixed}
 */
abstract class AbstractSourceParameters implements SourceParametersMutableInterface
{
    /**
     * @var array<non-empty-string, true>
     */
    protected array $nameCircularCallWatcher = [];

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->internalParameters());
    }

    public function get(string $name): array|bool|float|int|string|UnitEnum|null
    {
        if (!$this->has($name)) {
            $message = 0 < count($this->nameCircularCallWatcher)
                ? sprintf('%s: getting the container parameter via the placeholder "{%s}".', $this->getExceptionPartMessage(), $name)
                : '';

            throw new ParameterNotFoundException($message, name: $name);
        }

        try {
            if (isset($this->nameCircularCallWatcher[$name])) {
                throw new ParameterCallCircularException(names: [...array_keys($this->nameCircularCallWatcher), $name]);
            }

            $this->nameCircularCallWatcher[$name] = true;

            /**
             * @var bool                                               $isResolved
             * @var ($isResolve is true ? SourceParameterType : mixed) $value
             */
            [$isResolved, $value] = $this->internalParameters()[$name];

            if ($isResolved) {
                return $value; // @phpstan-ignore return.type
            }

            $resolvedValue = $this->resolveValue($value);
            $this->internalParameters()[$name] = [true, $resolvedValue];

            return $resolvedValue;
        } finally {
            unset($this->nameCircularCallWatcher[$name]);
        }
    }

    public function set(string $name, mixed $value): void
    {
        if (isset($this->internalParameters()[$name])) {
            throw new ParameterException(
                sprintf('The container parameter "%s" already defined.', $name)
            );
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

                if (!is_int($rK) && !is_string($rK)) {
                    throw new ParameterException(
                        sprintf('%s: Resolved array key "%s" got type "%s". Array key must be resolve as integer or string type.', $this->getExceptionPartMessage(), $k, get_debug_type($rK))
                    );
                }

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
     * @return SourceParameterType
     *
     * @throws ParameterNotFoundExceptionInterface
     * @throws ParameterExceptionInterface
     */
    protected function resolveString(string $value): array|bool|float|int|string|UnitEnum|null
    {
        if (!str_contains($value, '{')) {
            return $value;
        }

        if (1 === preg_match('/^{([^{\s]+)}$/', $value, $match)) {
            return $this->get($match[1]);
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
                throw new ParameterException(
                    sprintf('%s: cannot concatenate value from parameter placeholder "%s" as type "%s" into string. A part value must be presents as number or string types.', $this->getExceptionPartMessage(), $placeHolder, get_debug_type($partValue))
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
            sprintf('%s: the parameter "%s" has unsupported value type: "%s". The parameter value can be scalar, enumerated, or null.', $this->getExceptionPartMessage(), $resolvingName, get_debug_type($value))
        );
    }

    protected function getExceptionPartMessage(): string
    {
        return 1 < count($this->nameCircularCallWatcher)
            ? sprintf('An error occurred when resolving container parameters "%s"', implode('" -> "', array_keys($this->nameCircularCallWatcher)))
            : sprintf('An error occurred when resolving the container parameter "%s"', array_key_first($this->nameCircularCallWatcher));
    }

    /**
     * @return array<non-empty-string, SourceParameterRawType|SourceParameterResolvedType>
     */
    abstract protected function &internalParameters(): array;
}
