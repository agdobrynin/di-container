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
use function is_array;
use function is_string;
use function sprintf;

trait ParametersResolverTrait
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;
    use DiContainerTrait;

    private static int $variadicPosition = 0;

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
     * Resolved arguments mark as <isSingleton> by DiAttributeInterface.
     *
     * @var array<non-empty-string, mixed>
     */
    private array $resolvedArguments = [];

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @param array<non-empty-string|non-negative-int, mixed> $inputArguments
     * @param ReflectionParameter[]                           $reflectionParameters
     * @param bool                                            $isAttributeOnParamHigherPriority Php attributes higher priority then $inputArguments
     *
     * @return list<mixed>
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

        // Check valid user defined arguments
        $this->validateInputArguments();

        $dependencies = [];
        self::$variadicPosition = 0;
        $isUseAttribute = (bool) $this->getContainer()->getConfig()?->isUseAttribute();

        foreach ($this->reflectionParameters as $parameter) {
            $autowireException = null;

            try {
                if (false !== ($argumentNameOrIndex = $this->getArgumentByNameOrIndex($parameter))) {
                    // PHP attributes have higher priority than PHP definitions
                    if ($isUseAttribute && $isAttributeOnParamHigherPriority && ($resolvedParam = $this->attemptResolveParamByAttributes($parameter))->valid()) {
                        array_push($dependencies, ...$resolvedParam);

                        continue;
                    }

                    if ($parameter->isVariadic()) {
                        foreach ($this->getInputVariadicArgument($argumentNameOrIndex) as $definitionItem) {
                            $dependencies[] = $this->resolveInputArgument($parameter, $definitionItem);
                        }

                        break; // Variadic Parameter has last position
                    }

                    $dependencies[] = $this->resolveInputArgument($parameter, $this->arguments[$argumentNameOrIndex]);

                    continue;
                }

                if ($isUseAttribute && ($resolvedParam = $this->attemptResolveParamByAttributes($parameter))->valid()) {
                    array_push($dependencies, ...$resolvedParam);

                    continue;
                }

                $strType = $this->getParameterType($parameter, $this->getContainer());

                $dependencies[] = null === $strType
                    ? $this->getContainer()->get($parameter->getName())
                    : $this->getContainer()->get($strType);

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

            $declaredClass = null !== $parameter->getDeclaringClass() ? $parameter->getDeclaringClass()->getName() : '';
            $declaredFunction = $parameter->getDeclaringFunction()->getName();
            $where = implode('::', array_filter([$declaredClass, $declaredFunction])); // @phpstan-ignore arrayFilter.strict
            $messageParameter = $parameter.' in '.$where;
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
            $object = $o instanceof DiFactoryInterface
                ? $o($this->getContainer())
                : $o;

            if (true === ($argumentDefinition->isSingleton() ?? $this->getContainer()->getConfig()?->isSingletonServiceDefault())) {
                $identifier = sprintf('%s:%s', $parameter->getDeclaringFunction()->getName(), $parameter->getName());

                if ($parameter->isVariadic()) {
                    $identifier .= sprintf('#%d', self::$variadicPosition++);
                }

                return $this->resolvedArguments[$identifier] ??= $object;
            }

            return $object;
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
        $attrs = $this->getAttributeOnParameter($parameter);

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
                    new DiDefinitionProxyClosure($attr->getIdentifier(), $attr->isSingleton())
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
                    new DiDefinitionCallable($attr->getIdentifier(), $attr->isSingleton())
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
        if (is_string($argumentNameOrIndex)) {
            return is_array($this->arguments[$argumentNameOrIndex])
                ? $this->arguments[$argumentNameOrIndex]
                : [$this->arguments[$argumentNameOrIndex]];
        }

        return array_slice($this->arguments, $argumentNameOrIndex);
    }

    /**
     * @throws AutowireExceptionInterface
     */
    private function validateInputArguments(): void
    {
        if ([] !== $this->arguments) {
            $parameters = array_column($this->reflectionParameters, 'name');
            $hasVariadic = [] !== array_filter($this->reflectionParameters, static fn (ReflectionParameter $parameter) => $parameter->isVariadic());

            if (!$hasVariadic && count($this->arguments) > count($parameters)) {
                throw new AutowireException(
                    sprintf(
                        'Too many input arguments. Input index or name arguments "%s". Definition parameters: %s',
                        implode(', ', array_keys($this->arguments)),
                        '' !== ($p = implode(', ', $parameters)) ? '"'.$p.'"' : 'without input parameters'
                    )
                );
            }

            $argumentPosition = 0;

            foreach ($this->arguments as $name => $value) {
                ++$argumentPosition;

                if (is_string($name) && !in_array($name, $parameters, true)) {
                    throw new AutowireAttributeException(
                        sprintf(
                            'Invalid input argument name "%s" at position #%d. Definition %s has arguments: "%s"',
                            $name,
                            __CLASS__,
                            $argumentPosition,
                            implode(', ', $parameters)
                        )
                    );
                }
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
