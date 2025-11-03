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
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
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
 * @phpstan-import-type DiDefinitionArgumentType from DiDefinitionArgumentsInterface
 */
final class BuildArguments
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;

    /**
     * @param array<non-empty-string|non-negative-int, DiDefinitionArgumentType> $arguments
     */
    public function __construct(
        private readonly array $arguments,
        private readonly ReflectionFunctionAbstract $functionOrMethod,
        private readonly DiContainerInterface $container,
    ) {}

    /**
     * @param bool $isAttributeOnParamHigherPriority Php attributes higher priority then bindArguments
     *
     * @return (DiDefinitionAutowire|DiDefinitionCallable|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs|DiDefinitionValue|mixed)[]
     *
     * @throws AutowireExceptionInterface|ContainerNeedSetExceptionInterface
     */
    public function build(bool $isAttributeOnParamHigherPriority): array
    {
        $parameters = [];
        $isUseAttribute = (bool) $this->container->getConfig()?->isUseAttribute();

        foreach ($this->functionOrMethod->getParameters() as $parameter) {
            $argNameOrIndex = match (true) {
                array_key_exists($parameter->name, $this->arguments) => $parameter->name,
                array_key_exists($parameter->getPosition(), $this->arguments) => $parameter->getPosition(),
                default => false,
            };

            if (false !== $argNameOrIndex) {
                // PHP attributes have higher priority than PHP definitions
                if ($isUseAttribute && $isAttributeOnParamHigherPriority
                    && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                    array_push($parameters, ...$definitions);

                    continue;
                }

                if ($parameter->isVariadic()) {
                    foreach ($this->getVariadicArguments($argNameOrIndex) as $argKey => $definition) {
                        $parameters[$argKey] = $definition;
                    }

                    break; // Variadic Parameter has last position
                }

                $parameters[$parameter->getPosition()] = $this->arguments[$argNameOrIndex];

                continue;
            }

            if ($isUseAttribute && ($definitions = $this->getDefinitionByAttributes($parameter))->valid()) {
                array_push($parameters, ...$definitions);

                continue;
            }

            // The named argument for a variadic parameter can be a random string
            if ($parameter->isVariadic()) {
                foreach ($this->getVariadicArguments($parameter->name) as $argKey => $definition) {
                    $parameters[$argKey] = $definition;
                }

                break; // Variadic Parameter has last position
            }

            try {
                $strType = $this->getParameterType($parameter, $this->container);
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
        if (!$this->functionOrMethod->isVariadic()
            && (count($this->arguments) > ($c = count($this->functionOrMethod->getParameters())))) {
            $tailArgs = array_slice($this->arguments, $c, preserve_keys: true);

            $this->checkUnknownNamedParameter($tailArgs);
            array_push($parameters, ...$tailArgs);
        }

        return $parameters;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getVariadicArguments(int|string $argumentNameOrIndex): array
    {
        if (is_int($argumentNameOrIndex)) {
            return array_slice($this->arguments, $argumentNameOrIndex, preserve_keys: true);
        }

        $paramNames = array_column($this->functionOrMethod->getParameters(), 'name');

        return array_filter(
            $this->arguments,
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

    /**
     * @param array<non-empty-string|non-negative-int, mixed> $args
     *
     * @throws AutowireExceptionInterface
     */
    private function checkUnknownNamedParameter(array $args): void
    {
        foreach ($args as $key => $value) {
            if (is_string($key)) {
                throw new AutowireException(
                    sprintf('Does not accept unknown named parameter $%s in %s', $key, functionName($this->functionOrMethod))
                );
            }
        }
    }
}
