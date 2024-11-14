<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
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

    /**
     * Resolved arguments mark as <isSingleton> by DiAttributeInterface.
     */
    protected array $resolvedArguments = [];

    /**
     * User defined parameters by parameter name.
     *
     * @var array<string, mixed>
     */
    protected array $arguments = [];

    protected ContainerInterface $container;

    /**
     * @throws AutowiredAttributeException
     * @throws AutowiredExceptionInterface
     * @throws CallCircularDependency
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function resolveParameters(?bool $useAttribute): array
    {
        $dependencies = [];

        foreach ($this->reflectionParameters as $parameter) {
            if (\array_key_exists($parameter->name, $this->arguments)) {
                $argument = $this->arguments[$parameter->name];
                $args = \is_array($argument) && $parameter->isVariadic() ? $argument : [$argument];

                foreach ($args as $arg) {
                    $dependencies[] = \is_string($arg) && $this->container->has($arg)
                        ? $this->container->get($arg)
                        : $arg;
                }

                continue;
            }

            $autowireException = null;

            try {
                if ($useAttribute) {
                    $factories = DiFactory::makeFromReflection($parameter);

                    if ($factories->valid()) {
                        foreach ($factories as $factory) {
                            $dependencies[] = $this->resolveArgumentByAttribute($factory);
                        }

                        continue;
                    }

                    $injects = Inject::makeFromReflection($parameter, $this->container);

                    if ($injects->valid()) {
                        foreach ($injects as $inject) {
                            if (\class_exists($inject->getId())) {
                                $dependencies[] = $this->resolveArgumentByAttribute($inject);

                                continue;
                            }

                            $resolvedVal = $this->container->has($inject->getId())
                                ? $this->container->get($inject->getId())
                                : $this->container->get($parameter->getName());

                            $vals = \is_array($resolvedVal) && $parameter->isVariadic() ? $resolvedVal : [$resolvedVal];
                            \array_push($dependencies, ...$vals);
                        }

                        continue;
                    }
                }

                $parameterType = self::getParameterType($parameter, $this->container);

                $resolvedVal = null === $parameterType
                    ? $this->container->get($parameter->getName())
                    : $this->container->get($parameterType->getName());

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    protected function resolveArgumentByAttribute(DiAttributeInterface $attribute): mixed
    {
        if (isset($this->resolvedArguments[$attribute->getId()])) {
            return $this->resolvedArguments[$attribute->getId()];
        }

        $object = (new DiDefinitionAutowire(
            $this->container,
            $attribute->getId(),
            $attribute->isSingleton(),
            $attribute->getArguments()
        ))->invoke(true);

        $objectResult = $attribute instanceof DiFactory
            ? $object($this->container)
            : $object;

        return $attribute->isSingleton()
            ? $this->resolvedArguments[$attribute->getId()] = $objectResult
            : $objectResult;
    }
}
