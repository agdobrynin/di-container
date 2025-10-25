<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Generator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use ReflectionParameter;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
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
     * @throws AutowireExceptionInterface
     */
    private function getParameters(array $reflectionParameters, bool $isAttributeOnParamHigherPriority): array
    {
        if ([] === $this->bindArguments && [] === $reflectionParameters) {
            return [];
        }

        // Check valid user defined arguments
        $this->validateBindArguments($reflectionParameters);

        $parameters = [];
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();

        foreach ($this->reflectionParameters as $parameter) {
            if (false !== ($argumentNameOrIndex = $this->getBindArgumentByNameOrIndex($parameter))) {
                // PHP attributes have higher priority than PHP definitions
                if ($isUseAttribute && $isAttributeOnParamHigherPriority && ($definition = $this->getDefinitionByAttributes($parameter))->valid()) {
                    array_push($parameters, ...$definition);

                    continue;
                }
                // TODO - WIP
            }
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

    /**
     * @param ReflectionParameter[] $reflectionParameters
     *
     * @throws AutowireExceptionInterface
     */
    private function validateBindArguments(array $reflectionParameters): void
    {
        if ([] !== $this->bindArguments) {
            $parameters = array_column($reflectionParameters, 'name');
            $hasVariadic = [] !== array_filter($reflectionParameters, static fn (ReflectionParameter $parameter) => $parameter->isVariadic());

            if (!$hasVariadic && count($this->bindArguments) > count($parameters)) {
                throw new AutowireException(
                    sprintf(
                        'Too many input arguments. Input index or name arguments "%s". Definition parameters: %s',
                        implode(', ', array_keys($this->bindArguments)),
                        '' !== ($p = implode(', ', $parameters)) ? '"'.$p.'"' : 'without input parameters'
                    )
                );
            }

            $argumentPosition = 0;

            foreach ($this->bindArguments as $name => $value) {
                if (is_string($name) && !in_array($name, $parameters, true)) {
                    $reflectionParameter = $reflectionParameters[$argumentPosition];

                    throw new AutowireAttributeException(
                        sprintf(
                            'Invalid input argument name "%s" at position #%d. Definition %s has arguments: "%s"',
                            $name,
                            $argumentPosition,
                            implode('::', array_filter([$reflectionParameter->getDeclaringClass()?->getName(), $reflectionParameter->getDeclaringFunction()->getName().'()'])),
                            implode(', ', $parameters)
                        )
                    );
                }

                ++$argumentPosition;
            }
        }
    }
}
