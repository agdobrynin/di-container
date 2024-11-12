<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\ParameterTypeResolverTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ParametersResolverTrait
{
    use ParameterTypeResolverTrait;

    /**
     * @var \ReflectionParameter[]
     */
    protected array $reflectionParameters = [];
    protected array $resolvedArguments = [];

    /**
     * User defined parameters by parameter name.
     *
     * @var array<string, mixed>
     */
    protected array $arguments = [];

    /**
     * @throws AutowiredAttributeException
     * @throws AutowiredExceptionInterface
     * @throws CallCircularDependency
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function resolveParameters(ContainerInterface $container, ?bool $useAttribute): array
    {
        $dependencies = [];

        foreach ($this->reflectionParameters as $parameter) {
            if (\array_key_exists($parameter->name, $this->arguments)) {
                $argument = $this->arguments[$parameter->name];
                $args = \is_array($argument) && $parameter->isVariadic() ? $argument : [$argument];

                foreach ($args as $arg) {
                    $dependencies[] = \is_string($arg) && $container->has($arg)
                        ? $container->get($arg)
                        : $arg;
                }

                continue;
            }

            $autowireException = null;

            try {
                if ($useAttribute) {
                    if ($factories = DiFactory::makeFromReflection($parameter)) {
                        foreach ($factories as $factory) {
                            $dependencies[] = $this->resolvedArguments[$factory->id]
                                ?? $object = (new DiDefinitionAutowire($factory->id, $factory->isSingleton, $factory->arguments))
                                    ->invoke($container, true)($container)
                            ;

                            if (!isset($this->resolvedArguments[$factory->id]) && $factory->isSingleton && isset($object)) {
                                $this->resolvedArguments[$factory->id] = $object;
                            }
                        }

                        continue;
                    }

                    if ($injects = Inject::makeFromReflection($parameter, $container)) {
                        foreach ($injects as $inject) {
                            $injectDefinition = (string) $inject->id;

                            if (\class_exists($injectDefinition)) {
                                $dependencies[] = $this->resolvedArguments[$injectDefinition]
                                    ?? $object = (new DiDefinitionAutowire($injectDefinition, $inject->isSingleton, $inject->arguments))
                                        ->invoke($container, true)
                                ;

                                if (!isset($this->resolvedArguments[$injectDefinition]) && $inject->isSingleton && isset($object)) {
                                    $this->resolvedArguments[$injectDefinition] = $object;
                                }

                                continue;
                            }

                            $resolvedVal = $container->has($injectDefinition)
                                ? $container->get($injectDefinition)
                                : $container->get($parameter->getName());

                            $vals = \is_array($resolvedVal) && $parameter->isVariadic() ? $resolvedVal : [$resolvedVal];
                            \array_push($dependencies, ...$vals);
                        }

                        continue;
                    }
                }

                $parameterType = self::getParameterType($parameter, $container);

                $resolvedVal = null === $parameterType
                    ? $container->get($parameter->getName())
                    : $container->get($parameterType->getName());

                $vals = \is_array($resolvedVal) && $parameter->isVariadic() ? $resolvedVal : [$resolvedVal];
                \array_push($dependencies, ...$vals);

                continue;
            } catch (AutowiredAttributeException|CallCircularDependency $e) {
                throw $e;
            } catch (AutowiredExceptionInterface|ContainerExceptionInterface $e) {
                $autowireException = $e;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            $declaredClass = $parameter->getDeclaringClass()?->getName() ?: '';
            $declaredFunction = $parameter->getDeclaringFunction()->getName();
            $where = \implode('::', \array_filter([$declaredClass, $declaredFunction]));
            $messageParameter = $parameter.' in '.$where;
            $message = "Unresolvable dependency. {$messageParameter}. Reason: {$autowireException?->getMessage()}";

            if ($autowireException instanceof NotFoundExceptionInterface) {
                throw new NotFoundException(message: $message, previous: $autowireException);
            }

            throw new AutowiredException(message: $message, previous: $autowireException);
        }

        return $dependencies;
    }
}
