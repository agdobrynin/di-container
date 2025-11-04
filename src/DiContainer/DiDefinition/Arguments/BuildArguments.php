<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition\Arguments;

use Generator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait;
use ReflectionFunctionAbstract;
use ReflectionParameter;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_push;
use function array_slice;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function Kaspi\DiContainer\functionName;
use function sprintf;

/**
 * @phpstan-type DiDefinitionItem DiDefinitionAutowire|DiDefinitionCallable|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs|DiDefinitionValue
 */
final class BuildArguments
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionItem|mixed> $bindArguments
     */
    public function __construct(
        private readonly array $bindArguments,
        private readonly ReflectionFunctionAbstract $functionOrMethod,
        private readonly DiContainerInterface $container,
    ) {}

    /**
     * Build output arguments based on bind arguments and typed parameters
     * without PHP attributes.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireExceptionInterface
     */
    public function basedOnBindArguments(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $parameter) {
            if (false !== $this->pushFromBindArguments($args, $parameter)) {
                continue;
            }

            $this->pushFromParameterType($args, $parameter);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     * Build output arguments based on PHP attributes, bind arguments and typed parameters.
     * PHP attribute on a parameter has higher priority than bind argument.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireExceptionInterface
     */
    public function basedOnPhpAttributes(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $parameter) {
            if (($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                array_push($args, ...$definitions);

                continue;
            }

            if (false !== $this->pushFromBindArguments($args, $parameter)) {
                continue;
            }

            $this->pushFromParameterType($args, $parameter);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     *  Build output arguments based on bind arguments, PHP attributes and typed parameters.
     *  A bind argument on a parameter has higher priority than PHP attribute.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireExceptionInterface
     */
    public function basedOnBindArgumentsAsPriorityAndPhpAttributes(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $parameter) {
            if (false !== $this->pushFromBindArguments($args, $parameter)) {
                continue;
            }

            if (($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                array_push($args, ...$definitions);

                continue;
            }

            $this->pushFromParameterType($args, $parameter);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionItem|mixed> $args
     *
     * @throws AutowireParameterTypeException
     */
    private function pushFromParameterType(array &$args, ReflectionParameter $parameter): void
    {
        try {
            $strType = $this->getParameterType($parameter, $this->container);
            // @phpstan-ignore parameterByRef.type
            $args[$parameter->getPosition()] = new DiDefinitionGet($strType);
        } catch (AutowireParameterTypeException $e) {
            if (!$parameter->isDefaultValueAvailable()) {
                throw $e;
            }
        }
    }

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionItem|mixed> $args
     *
     * @return bool when argument found return true
     */
    private function pushFromBindArguments(array &$args, ReflectionParameter $parameter): bool
    {
        $argNameOrIndex = match (true) {
            array_key_exists($parameter->name, $this->bindArguments) => $parameter->name,
            array_key_exists($parameter->getPosition(), $this->bindArguments) => $parameter->getPosition(),
            default => false,
        };

        if (false !== $argNameOrIndex) {
            if ($parameter->isVariadic()) {
                foreach ($this->capturingVariadicArguments($argNameOrIndex) as $argKey => $definition) {
                    $args[$argKey] = $definition;
                }

                return true; // Variadic Parameter has last position
            }

            // @phpstan-ignore parameterByRef.type
            $args[$parameter->getPosition()] = $this->bindArguments[$argNameOrIndex];

            return true;
        }

        /*
         * Even if the binding argument is not found by position or named argument,
         * it is possible to pass a named argument by any name.
         */
        if ($parameter->isVariadic()) {
            foreach ($this->capturingVariadicArguments($parameter->name) as $argKey => $definition) {
                $args[$argKey] = $definition;
            }

            return true; // Variadic Parameter has last position
        }

        return false;
    }

    /**
     * Add unused bind arguments.
     * This can be useful for functions without arguments or tail argument
     * that use functions like `func_get_args()` or any `func_*()`.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireException
     */
    private function getTailArguments(): array
    {
        if (!$this->functionOrMethod->isVariadic()
            && (count($this->bindArguments) > ($c = count($this->functionOrMethod->getParameters())))) {
            $tailArgs = array_slice($this->bindArguments, $c, preserve_keys: true);

            foreach ($tailArgs as $key => $value) {
                if (is_string($key)) {
                    throw new AutowireException(
                        sprintf('Does not accept unknown named parameter $%s in %s', $key, functionName($this->functionOrMethod))
                    );
                }
            }

            return $tailArgs;
        }

        return [];
    }

    /**
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     */
    private function capturingVariadicArguments(int|string $argumentNameOrIndex): array
    {
        if (is_int($argumentNameOrIndex)) {
            return array_slice($this->bindArguments, $argumentNameOrIndex, preserve_keys: true);
        }

        $paramNames = array_column($this->functionOrMethod->getParameters(), 'name');

        return array_filter(
            $this->bindArguments,
            static fn (int|string $nameOrIndex) => !in_array($nameOrIndex, $paramNames, true) || $nameOrIndex === $argumentNameOrIndex,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return Generator<DiDefinitionItem>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    private function getDefinitionByAttributes(ReflectionParameter $parameter): Generator
    {
        $attrs = $this->getAttributeOnParameter($parameter, $this->container);

        if (!$attrs->valid()) {
            return;
        }

        foreach ($attrs as $attr) {
            if ($attr instanceof Inject) {
                $definition = new DiDefinitionGet($attr->getIdentifier()); // @phpstan-ignore argument.type
            } elseif ($attr instanceof ProxyClosure) {
                $definition = new DiDefinitionProxyClosure($attr->getIdentifier());
            } elseif ($attr instanceof TaggedAs) {
                $definition = new DiDefinitionTaggedAs(
                    $attr->getIdentifier(),
                    $attr->isLazy(),
                    $attr->getPriorityDefaultMethod(),
                    $attr->isUseKeys(),
                    $attr->getKey(),
                    $attr->getKeyDefaultMethod(),
                    $attr->getContainerIdExclude(),
                    $attr->isSelfExclude(),
                );
            } else {
                $definition = new DiDefinitionCallable($attr->getIdentifier());
            }

            yield $definition;
        }
    }
}
