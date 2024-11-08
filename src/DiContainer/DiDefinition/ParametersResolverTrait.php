<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\ParameterTypeResolverTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ParametersResolverTrait
{
    use ParameterTypeResolverTrait;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectionParameters = [];

    /**
     * User defined parameters by parameter name.
     *
     * @var array<string, mixed>
     */
    private array $arguments = [];

    /**
     * @throws AutowiredAttributeException
     * @throws AutowiredExceptionInterface
     * @throws CallCircularDependency
     * @throws NotFoundException
     */
    private function resolveParameters(DiContainerInterface $container, ?bool $useAttribute): array
    {
        // @todo need prepare parameters for resolve with cache order.
        $dependencies = [];

        foreach ($this->reflectionParameters as $parameter) {
            if (isset($this->arguments[$parameter->name])) {
                $argument = $this->arguments[$parameter->name];
                $args = \is_array($argument) && $parameter->isVariadic() ? $argument : [$argument];

                foreach ($args as $arg) {
                    if (\is_string($arg)) {
                        try {
                            $dependencies[] = $container->get($arg);
                        } catch (NotFoundExceptionInterface) {
                            $dependencies[] = $arg;
                        }

                        continue;
                    }

                    $dependencies[] = $arg;
                }

                continue;
            }

            $autowireException = null;

            try {
                if ($useAttribute) {
                    if ($factories = DiFactory::makeFromReflection($parameter)) {
                        foreach ($factories as $key => $factory) {
                            $dependencyKey = $this->registerDefinition($parameter, $container, $factory->id, $factory->arguments, $factory->isSingleton, $key);
                            $dependencies[] = $container->get($dependencyKey);
                        }

                        continue;
                    }

                    if ($injects = Inject::makeFromReflection($parameter, $container)) {
                        foreach ($injects as $key => $inject) {
                            $injectDefinition = (string) $inject->id;

                            if (\interface_exists($injectDefinition)) {
                                $service = Service::makeFromReflection(new \ReflectionClass($injectDefinition))
                                    ?: throw new AutowiredException(
                                        "The interface [{$injectDefinition}] is not defined via the php-attribute like #[Service]."
                                    );
                                $dependencyKey = $this->registerDefinition($parameter, $container, $service->id, $service->arguments, $service->isSingleton);
                                $dependencies[] = $container->get($dependencyKey);

                                continue;
                            }

                            if (!\class_exists($injectDefinition)) {
                                try {
                                    $val = $container->get($injectDefinition);
                                } catch (NotFoundExceptionInterface) {
                                    $val = $container->get($parameter->getName());
                                }

                                $vals = \is_array($val) && $parameter->isVariadic() ? $val : [$val];

                                foreach ($vals as $val) {
                                    $dependencies[] = $val;
                                }

                                continue;
                            }

                            $dependencyKey = $this->registerDefinition($parameter, $container, $inject->id, $inject->arguments, $inject->isSingleton, $key);
                            $dependencies[] = $container->get($dependencyKey);
                        }

                        continue;
                    }
                }

                $parameterType = self::getParameterType($parameter, $container);

                $val = null === $parameterType
                    ? $container->get($parameter->getName())
                    : $container->get($parameterType->getName());

                $vals = \is_array($val) && $parameter->isVariadic() ? $val : [$val];

                foreach ($vals as $val) {
                    $dependencies[] = $val;
                }

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

    private function registerDefinition(\ReflectionParameter $parameter, DiContainerInterface $container, mixed $definition, array $arguments, bool $isSingleton, int $variadicPosition = 0): string
    {
        $fnName = $parameter->getDeclaringFunction();
        $target = $parameter->getDeclaringClass()?->getName() ?: $fnName->getName().$fnName->getStartLine();
        $variadicKey = $parameter->isVariadic() ? \sprintf('#variadic%d', $variadicPosition) : '';
        $dependencyKey = $target.'::'.$fnName->getName().'::'.$parameter->getType().':'.$parameter->getPosition().$variadicKey;

        try {
            $container->set(id: $dependencyKey, definition: $definition, arguments: $arguments, isSingleton: $isSingleton);
        } catch (ContainerAlreadyRegisteredException) {
        }

        return $dependencyKey;
    }
}
