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
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use ReflectionParameter;

use function array_key_exists;
use function array_push;
use function array_slice;
use function array_values;
use function count;
use function end;
use function is_array;
use function is_string;
use function Kaspi\DiContainer\functionNameByParameter;
use function sprintf;

trait BindArgumentsTrait
{
    use DiContainerTrait;

    /**
     * User defined parameters by parameter name.
     *
     * @var array<non-empty-string|non-negative-int, mixed>
     */
    private array $bindArguments = [];

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArguments = $argument; // @phpstan-ignore assign.propertyType

        return $this;
    }

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @return array<non-empty-string|non-negative-int, mixed>
     */
    private function getBindArguments(): array
    {
        return $this->bindArguments;
    }

    /**
     * @param ReflectionParameter[] $reflectionParameters
     * @param bool                  $isAttributeOnParamHigherPriority Php attributes higher priority then bindArguments
     *
     * @return (DiDefinitionAutowire|DiDefinitionCallable|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs|mixed)[]
     *
     * @throws AutowireExceptionInterface|ContainerNeedSetExceptionInterface
     */
    private function getParameters(array $reflectionParameters, bool $isAttributeOnParamHigherPriority): array
    {
        if ([] === $reflectionParameters) {
            /*
             * This can be useful for functions without arguments
             * that use functions like `func_get_args()` or any `func_*()`
             */
            return $this->bindArguments;
        }

        $parameters = [];
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();
        $hasVariadic = end($reflectionParameters)->isVariadic();

        foreach ($reflectionParameters as $parameter) {
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

            try {
                $strType = $this->getParameterType($parameter, $this->getContainer());
            } catch (AutowireExceptionInterface $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $parameters[] = $parameter->getDefaultValue();

                    continue;
                }

                throw $e;
            }

            if (null !== $strType && $this->getContainer()->has($strType)) {
                $parameters[] = new DiDefinitionGet($strType);

                continue;
            }

            if ($this->getContainer()->has($parameter->getName())) {
                $parameters[] = new DiDefinitionGet($parameter->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            throw new AutowireException(
                sprintf('Unresolvable dependency %s in %s.', $parameter, functionNameByParameter($parameter)),
            );
        }

        /*
         * Add unused bind arguments.
         * This can be useful for functions without arguments
         * that use functions like `func_get_args()` or any `func_*()`
         */
        if (!$hasVariadic && count($this->bindArguments) > ($c = count($reflectionParameters))) {
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
     * @return Generator<DiDefinitionCallable>|Generator<DiDefinitionGet>|Generator<DiDefinitionProxyClosure>|Generator<DiDefinitionTaggedAs>
     *
     * @throws AutowireExceptionInterface
     */
    private function getDefinitionByAttributes(ReflectionParameter $parameter): Generator
    {
        $attrs = $this->getAttributeOnParameter($parameter);

        if (!$attrs->valid()) {
            return;
        }

        foreach ($attrs as $attr) {
            if ($attr instanceof Inject) {
                $definition = '' !== $attr->getIdentifier()
                    ? new DiDefinitionGet($attr->getIdentifier())
                    : new DiDefinitionGet($parameter->getName());
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
