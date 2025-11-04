<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Generator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionParameter;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_push;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function Kaspi\DiContainer\functionName;
use function sprintf;

trait ParametersResolverTrait
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;
    use DiContainerTrait;

    /**
     * User defined input arguments.
     *
     * @var array<non-empty-string|non-negative-int, mixed>
     */
    private array $arguments;

    /**
     * Reflected parameters from function or method.
     *
     * @var ReflectionParameter[]
     */
    private array $reflectionParameters;

    /**
     * @var string[]
     */
    private array $parameterNames;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @param array<non-empty-string|non-negative-int, mixed> $inputArguments
     * @param ReflectionParameter[]                           $reflectionParameters
     * @param bool                                            $isAttributeOnParamHigherPriority Php attributes higher priority then $inputArguments
     *
     * @return array<int|string, mixed>
     *
     * @throws AutowireAttributeException
     * @throws AutowireExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function resolveParameters(array $inputArguments, array $reflectionParameters, bool $isAttributeOnParamHigherPriority): array
    {
        if ([] === $inputArguments && [] === $reflectionParameters) {
            return [];
        }

        $this->arguments = $inputArguments;
        $this->reflectionParameters = $reflectionParameters;
        $this->parameterNames = array_column($this->reflectionParameters, 'name');

        // Check valid user defined arguments
        $this->validateInputArguments();

        $dependencies = [];
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();

        foreach ($this->reflectionParameters as $parameter) {
            $autowireException = null;

            try {
                if (false !== ($foundArgumentNameOrIndex = $this->getArgumentByNameOrIndex($parameter))) {
                    // PHP attributes have higher priority than PHP definitions
                    if ($isUseAttribute && $isAttributeOnParamHigherPriority && ($resolvedParam = $this->attemptResolveParamByAttributes($parameter))->valid()) {
                        array_push($dependencies, ...$resolvedParam);

                        continue;
                    }

                    if ($parameter->isVariadic()) {
                        foreach ($this->getInputVariadicArgument($foundArgumentNameOrIndex) as $argNameOrIndex => $definitionItem) {
                            $dependencies[$argNameOrIndex] = $this->resolveInputArgument($parameter, $definitionItem);
                        }

                        break; // Variadic Parameter has last position
                    }

                    $dependencies[] = $this->resolveInputArgument($parameter, $this->arguments[$foundArgumentNameOrIndex]);

                    continue;
                }

                if ($isUseAttribute && ($resolvedParam = $this->attemptResolveParamByAttributes($parameter))->valid()) {
                    array_push($dependencies, ...$resolvedParam);

                    continue;
                }

                if ($parameter->isVariadic()) {
                    foreach ($this->getInputVariadicArgument($parameter->getName()) as $argName => $definitionItem) {
                        $dependencies[$argName] = $this->resolveInputArgument($parameter, $definitionItem);
                    }

                    break; // Variadic Parameter has last position
                }

                $strType = $this->getParameterType($parameter, $this->getContainer());

                $dependencies[] = $this->getContainer()->get($strType);

                continue;
            } catch (AutowireAttributeException|CallCircularDependencyException|ContainerNeedSetExceptionInterface $e) {
                throw $e;
            } catch (AutowireExceptionInterface|ContainerExceptionInterface $e) {
                $autowireException = $e;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->isOptional()) {
                continue;
            }

            $messageParameter = $parameter.' in '.functionName($parameter->getDeclaringFunction());
            $message = "Unresolvable dependency. {$messageParameter}. Reason: {$autowireException->getMessage()}";

            if ($autowireException instanceof NotFoundExceptionInterface) {
                throw new NotFoundException(message: $message, previous: $autowireException);
            }

            throw new AutowireException(message: $message, previous: $autowireException);
        }

        return $dependencies;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowireExceptionInterface
     */
    private function resolveInputArgument(ReflectionParameter $parameter, mixed $argumentDefinition): mixed
    {
        if ($argumentDefinition instanceof DiDefinitionLinkInterface) {
            return $this->getContainer()->get($argumentDefinition->getDefinition());
        }

        if ($argumentDefinition instanceof DiDefinitionTaggedAsInterface) {
            if ($this instanceof DiDefinitionAutowireInterface) {
                $argumentDefinition->setCallingByService($this);
            }

            return $argumentDefinition->setContainer($this->getContainer())
                ->getServicesTaggedAs()
            ;
        }

        if ($argumentDefinition instanceof DiDefinitionInvokableInterface) {
            $o = $argumentDefinition->setContainer($this->getContainer())->invoke();

            return $o instanceof DiFactoryInterface
                ? $o($this->getContainer())
                : $o;
        }

        if ($argumentDefinition instanceof DiDefinitionInterface) {
            return $argumentDefinition->getDefinition();
        }

        return $argumentDefinition;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws AutowireExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function attemptResolveParamByAttributes(ReflectionParameter $parameter): Generator
    {
        $attrs = $this->getAttributeOnParameter($parameter, $this->getContainer());

        if (!$attrs->valid()) {
            return;
        }

        foreach ($attrs as $attr) {
            if ($attr instanceof Inject) {
                $resolvedParam = '' !== $attr->getIdentifier()
                    ? $this->getContainer()->get($attr->getIdentifier())
                    : $this->getContainer()->get($parameter->getName());
            } elseif ($attr instanceof ProxyClosure) {
                $resolvedParam = $this->resolveInputArgument(
                    $parameter,
                    new DiDefinitionProxyClosure($attr->getIdentifier())
                );
            } elseif ($attr instanceof TaggedAs) {
                $resolvedParam = $this->resolveInputArgument(
                    $parameter,
                    new DiDefinitionTaggedAs(
                        $attr->getIdentifier(),
                        $attr->isLazy(),
                        $attr->getPriorityDefaultMethod(),
                        $attr->isUseKeys(),
                        $attr->getKey(),
                        $attr->getKeyDefaultMethod(),
                        $attr->getContainerIdExclude(),
                        $attr->isSelfExclude(),
                    )
                );
            } else {
                $resolvedParam = $this->resolveInputArgument(
                    $parameter,
                    new DiDefinitionCallable($attr->getIdentifier())
                );
            }

            yield $resolvedParam;
        }
    }

    /**
     * @return array<mixed>
     */
    private function getInputVariadicArgument(int|string $argumentNameOrIndex): array
    {
        if (is_int($argumentNameOrIndex)) {
            return array_slice($this->arguments, $argumentNameOrIndex, preserve_keys: true);
        }

        $paramNames = $this->parameterNames;

        return array_filter(
            $this->arguments,
            static fn (int|string $nameOrIndex) => !in_array($nameOrIndex, $paramNames, true) || $nameOrIndex === $argumentNameOrIndex,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @throws AutowireExceptionInterface
     */
    private function validateInputArguments(): void
    {
        if ([] !== $this->arguments) {
            $hasVariadic = [] !== array_filter($this->reflectionParameters, static fn (ReflectionParameter $parameter) => $parameter->isVariadic());

            if ($hasVariadic) {
                return;
            }

            if (count($this->arguments) > count($this->parameterNames)) {
                throw new AutowireException(
                    sprintf(
                        'Too many input arguments. Input index or name arguments "%s". Definition parameters: %s',
                        implode(', ', array_keys($this->arguments)),
                        '' !== ($p = implode(', ', $this->parameterNames)) ? '"'.$p.'"' : 'without input parameters'
                    )
                );
            }

            $argumentPosition = 0;

            foreach ($this->arguments as $name => $value) {
                if (is_string($name) && !in_array($name, $this->parameterNames, true)) {
                    $reflectionParameter = $this->reflectionParameters[$argumentPosition];

                    throw new AutowireAttributeException(
                        sprintf(
                            'Invalid input argument name "%s" at position #%d. Definition %s has arguments: "%s"',
                            $name,
                            $argumentPosition,
                            implode('::', array_filter([$reflectionParameter->getDeclaringClass()?->getName(), $reflectionParameter->getDeclaringFunction()->getName().'()'])),
                            implode(', ', $this->parameterNames)
                        )
                    );
                }

                ++$argumentPosition;
            }
        }
    }

    private function getArgumentByNameOrIndex(ReflectionParameter $parameter): false|int|string
    {
        if ([] === $this->arguments) {
            return false;
        }

        return match (true) {
            array_key_exists($parameter->name, $this->arguments) => $parameter->name,
            array_key_exists($parameter->getPosition(), $this->arguments) => $parameter->getPosition(),
            default => false,
        };
    }
}
