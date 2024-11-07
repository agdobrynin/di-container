<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

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
use Kaspi\DiContainer\Interfaces\ParametersResolverInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ParametersResolver implements ParametersResolverInterface
{
    use ParameterTypeResolverTrait;

    public function __construct(private DiContainerInterface $container, private bool $useAttribute) {}

    public function resolve(array $reflectionParameters, array $customArguments): array
    {
        $dependencies = [];

        foreach ($reflectionParameters as $parameter) {
            if (isset($customArguments[$parameter->name])) {
                $argument = $customArguments[$parameter->name];
                $args = \is_array($argument) && $parameter->isVariadic() ? $argument : [$argument];

                foreach ($args as $arg) {
                    if (\is_string($arg)) {
                        try {
                            $dependencies[] = $this->container->get($arg);
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
                if ($this->useAttribute) {
                    if ($factories = DiFactory::makeFromReflection($parameter)) {
                        foreach ($factories as $key => $factory) {
                            $dependencyKey = $this->registerDefinition($parameter, $factory->id, $factory->arguments, $factory->isSingleton, $key);
                            $dependencies[] = $this->container->get($dependencyKey);
                        }

                        continue;
                    }

                    if ($injects = Inject::makeFromReflection($parameter, $this->container)) {
                        foreach ($injects as $key => $inject) {
                            $injectDefinition = (string) $inject->id;

                            if (\interface_exists($injectDefinition)) {
                                $service = Service::makeFromReflection(new \ReflectionClass($injectDefinition))
                                    ?: throw new AutowiredException(
                                        "The interface [{$injectDefinition}] is not defined via the php-attribute like #[Service]."
                                    );
                                $dependencyKey = $this->registerDefinition($parameter, $service->id, $service->arguments, $service->isSingleton);
                                $dependencies[] = $this->container->get($dependencyKey);

                                continue;
                            }

                            if (!\class_exists($injectDefinition)) {
                                try {
                                    $val = $this->container->get($injectDefinition);
                                } catch (NotFoundExceptionInterface) {
                                    $val = $this->container->get($parameter->getName());
                                }

                                $vals = \is_array($val) && $parameter->isVariadic() ? $val : [$val];

                                foreach ($vals as $val) {
                                    $dependencies[] = $val;
                                }

                                continue;
                            }

                            $dependencyKey = $this->registerDefinition($parameter, $inject->id, $inject->arguments, $inject->isSingleton, $key);
                            $dependencies[] = $this->container->get($dependencyKey);
                        }

                        continue;
                    }
                }

                $parameterType = self::getParameterType($parameter, $this->container);

                $val = null === $parameterType
                    ? $this->container->get($parameter->getName())
                    : $this->container->get($parameterType->getName());

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

    protected function registerDefinition(\ReflectionParameter $parameter, mixed $definition, array $arguments, bool $isSingleton, int $variadicPosition = 0): string
    {
        $fnName = $parameter->getDeclaringFunction();
        $target = $parameter->getDeclaringClass()?->getName() ?: $fnName->getName().$fnName->getStartLine();
        $variadicKey = $parameter->isVariadic() ? \sprintf('#variadic%d', $variadicPosition) : '';
        $dependencyKey = $target.'::'.$fnName->getName().'::'.$parameter->getType().':'.$parameter->getPosition().$variadicKey;

        try {
            $this->container->set(id: $dependencyKey, definition: $definition, arguments: $arguments, isSingleton: $isSingleton);
        } catch (ContainerAlreadyRegisteredException) {
        }

        return $dependencyKey;
    }
}
