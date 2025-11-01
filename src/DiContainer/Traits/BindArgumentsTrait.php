<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

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
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
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

use const ARRAY_FILTER_USE_KEY;

trait BindArgumentsTrait
{
    use DiContainerTrait;
    use ParameterTypeByReflectionTrait;

    /**
     * User defined parameters by parameter name.
     *
     * @var array<non-empty-string|non-negative-int, mixed>
     */
    private array $bindArguments = [];

    abstract public function getContainer(): DiContainerInterface;

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArguments = $argument; // @phpstan-ignore assign.propertyType

        return $this;
    }

    /**
     * @return array<non-empty-string|non-negative-int, mixed>
     */
    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }

    /**
     * @param bool $isAttributeOnParamHigherPriority Php attributes higher priority then bindArguments
     *
     * @return (DiDefinitionAutowire|DiDefinitionCallable|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs|DiDefinitionValue|mixed)[]
     *
     * @throws AutowireExceptionInterface|ContainerNeedSetExceptionInterface
     */
    private function buildArguments(ReflectionFunctionAbstract $functionOrMethod, bool $isAttributeOnParamHigherPriority): array
    {
        $parameters = [];
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();

        foreach ($functionOrMethod->getParameters() as $parameter) {
            $argNameOrIndex = match (true) {
                array_key_exists($parameter->name, $this->bindArguments) => $parameter->name,
                array_key_exists($parameter->getPosition(), $this->bindArguments) => $parameter->getPosition(),
                default => false,
            };

            if (false !== $argNameOrIndex) {
                // PHP attributes have higher priority than PHP definitions
                if ($isUseAttribute && $isAttributeOnParamHigherPriority
                    && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                    foreach ($definitions as $definition) {
                        $parameters[$parameter->getPosition()] = $definition;
                    }

                    continue;
                }

                if ($parameter->isVariadic()) {
                    foreach ($this->getVariadicArguments($argNameOrIndex, $functionOrMethod) as $key => $definition) {
                        $parameters[$key] = $definition;
                    }

                    break; // Variadic Parameter has last position
                }

                $parameters[$argNameOrIndex] = $this->bindArguments[$argNameOrIndex];

                continue;
            }

            if ($isUseAttribute && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                if ($parameter->isVariadic()) {
                    array_push($parameters, ...$definitions);

                    break; // Variadic Parameter has last position
                }

                $parameters[$parameter->getPosition()] = $definitions->current();

                continue;
            }

            // The named argument for a variadic parameter can be a random string
            if ($parameter->isVariadic()) {
                foreach ($this->getVariadicArguments($parameter->name, $functionOrMethod) as $key => $definition) {
                    $parameters[$key] = $definition;
                }

                break; // Variadic Parameter has last position
            }

            try {
                $strType = $this->getParameterType($parameter, $this->getContainer());
                $parameters[$parameter->getPosition()] = new DiDefinitionGet($strType);

                continue;
            } catch (AutowireParameterTypeException $e) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw $e;
                }
            }
        }

        /*
         * Add unused bind arguments.
         * This can be useful for functions without arguments or tail argument
         * that use functions like `func_get_args()` or any `func_*()`
         */
        if (!$functionOrMethod->isVariadic()
            && count($this->bindArguments) > ($c = count($functionOrMethod->getParameters()))) {
            $tailArgs = array_slice($this->bindArguments, $c, preserve_keys: true);

            $this->checkUnknownNamedParameter($functionOrMethod, $tailArgs);
            array_push($parameters, ...$tailArgs);
        }

        return $parameters;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getVariadicArguments(int|string $argumentNameOrIndex, ReflectionFunctionAbstract $functionOrMethod): array
    {
        if (is_int($argumentNameOrIndex)) {
            return array_slice($this->bindArguments, $argumentNameOrIndex, preserve_keys: true);
        }

        $paramNames = array_column($functionOrMethod->getParameters(), 'name');

        return array_filter(
            $this->bindArguments,
            static fn (int|string $nameOrIndex) => !in_array($nameOrIndex, $paramNames, true) || $nameOrIndex === $argumentNameOrIndex,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return Generator<DiDefinitionCallable>|Generator<DiDefinitionGet>|Generator<DiDefinitionProxyClosure>|Generator<DiDefinitionTaggedAs>|Generator<DiDefinitionValue>
     *
     * @throws AutowireExceptionInterface|ContainerNeedSetExceptionInterface
     */
    private function getDefinitionByAttributes(ReflectionParameter $parameter): Generator
    {
        $attrs = $this->getAttributeOnParameter($parameter, $this->getContainer());

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

    /**
     * @param array<non-empty-string|non-negative-int, mixed> $args
     *
     * @throws AutowireExceptionInterface
     */
    private function checkUnknownNamedParameter(ReflectionFunctionAbstract $functionOrMethod, array $args): void
    {
        $findArgNameAsString = static function (array $array) {
            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    return $key;
                }
            }

            return null;
        };

        if ([] === $functionOrMethod->getParameters()) {
            if (null !== ($argStringName = $findArgNameAsString($args))) {
                throw new AutowireException(
                    sprintf('Does not accept unknown named parameter $%s in %s', $argStringName, functionName($functionOrMethod))
                );
            }

            return;
        }

        if (null !== ($argStringName = $findArgNameAsString($args))) {
            throw new AutowireException(
                sprintf('Does not accept unknown named parameter $%s in %s', $argStringName, functionName($functionOrMethod))
            );
        }
    }
}
