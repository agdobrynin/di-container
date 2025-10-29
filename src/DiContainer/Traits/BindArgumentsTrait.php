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
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;

use function array_key_exists;
use function array_push;
use function array_slice;
use function array_values;
use function count;
use function is_array;
use function is_string;

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
        if ([] === $functionOrMethod->getParameters()) {
            /*
             * This maybe useful for functions without arguments
             * that use functions like `func_get_args()` or any `func_*()`
             */
            return $this->bindArguments;
        }

        $parameters = [];
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();
        $hasVariadic = $functionOrMethod->isVariadic();

        foreach ($functionOrMethod->getParameters() as $parameter) {
            if (false !== ($argumentNameOrIndex = $this->getBindArgumentByNameOrIndex($parameter))) {
                // PHP attributes have higher priority than PHP definitions
                if ($isUseAttribute && $isAttributeOnParamHigherPriority
                    && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                    array_push($parameters, ...$definitions);

                    continue;
                }

                if ($parameter->isVariadic()) {
                    foreach ($this->getBindArgumentAsVariadic($argumentNameOrIndex) as $definition) {
                        $parameters[] = $definition;
                    }

                    break; // Variadic Parameter has last position
                }

                $parameters[] = $this->bindArguments[$argumentNameOrIndex];

                continue;
            }

            if ($isUseAttribute && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                array_push($parameters, ...$definitions);

                continue;
            }

            // Variadic parameter resolve only by bind arguments
            if ($parameter->isVariadic()) {
                break; // Variadic Parameter has last position
            }

            try {
                $strType = $this->getParameterType($parameter, $this->getContainer());
                $parameters[] = new DiDefinitionGet($strType);

                continue;
            } catch (AutowireParameterTypeException $e) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw $e;
                }
            }
        }

        /*
         * Add unused bind arguments.
         * This can be useful for functions without arguments
         * that use functions like `func_get_args()` or any `func_*()`
         */
        if (!$hasVariadic && count($this->bindArguments) > ($c = count($functionOrMethod->getParameters()))) {
            array_push($parameters, ...array_values(array_slice($this->bindArguments, $c)));
        }

        return $parameters;
    }

    private function getBindArgumentByNameOrIndex(ReflectionParameter $parameter): false|int|string
    {
        if ([] === $this->bindArguments) {
            return false;
        }

        return match (true) {
            array_key_exists($parameter->name, $this->bindArguments) => $parameter->name,
            array_key_exists($parameter->getPosition(), $this->bindArguments) => $parameter->getPosition(),
            default => false,
        };
    }

    /**
     * @return array<mixed>
     */
    private function getBindArgumentAsVariadic(int|string $argumentNameOrIndex): array
    {
        if (is_string($argumentNameOrIndex)) {
            return is_array($this->bindArguments[$argumentNameOrIndex])
                ? $this->bindArguments[$argumentNameOrIndex]
                : [$this->bindArguments[$argumentNameOrIndex]];
        }

        return array_slice($this->bindArguments, $argumentNameOrIndex);
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
                $definition = new DiDefinitionProxyClosure($attr->getIdentifier(), $attr->isSingleton());
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
                $definition = new DiDefinitionCallable($attr->getIdentifier(), $attr->isSingleton());
            }

            yield $definition;
        }
    }
}
