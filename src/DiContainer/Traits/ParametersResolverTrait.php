<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ParametersResolverTrait
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;
    use PsrContainerTrait;

    /**
     * @var \ReflectionParameter[]
     */
    protected array $reflectionParameters;

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

    /**
     * @throws AutowiredAttributeException
     * @throws AutowiredExceptionInterface
     * @throws CallCircularDependency
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function resolveParameters(?bool $useAttribute = null): array
    {
        $dependencies = [];

        foreach ($this->reflectionParameters as $parameter) {
            if (\array_key_exists($parameter->name, $this->arguments)) {
                $argument = $this->arguments[$parameter->name];
                $args = \is_array($argument) && $parameter->isVariadic()
                    ? $argument
                    : [$argument];

                foreach ($args as $arg) {
                    $dependencies[] = match (true) {
                        $arg instanceof DiDefinitionReference => $this->getContainer()->get($arg->getDefinition()),
                        \is_string($arg) && $this->getContainer()->has($arg) => $this->getContainer()->get($arg),
                        default => $arg, // @todo how detect value type?
                    };
                }

                continue;
            }

            $autowireException = null;

            try {
                if ($useAttribute) {
                    if (($factories = $this->getDiFactoryAttribute($parameter))
                        && $factories->valid()) {
                        foreach ($factories as $factory) {
                            $dependencies[] = $this->resolveArgumentByAttribute($factory);
                        }

                        continue;
                    }

                    if (($injects = $this->getInjectAttribute($parameter))
                        && $injects->valid()) {
                        foreach ($injects as $inject) {
                            if (\class_exists($inject->getIdentifier())) {
                                $dependencies[] = $this->resolveArgumentByAttribute($inject);

                                continue;
                            }

                            $resolvedVal = $this->getContainer()->has($inject->getIdentifier())
                                ? $this->getContainer()->get($inject->getIdentifier())
                                : $this->getContainer()->get($parameter->getName());

                            $vals = \is_array($resolvedVal) && $parameter->isVariadic()
                                ? $resolvedVal
                                : [$resolvedVal];
                            \array_push($dependencies, ...$vals);
                        }

                        continue;
                    }

                    if (($injectsByReference = $this->getInjectByReferenceAttribute($parameter))
                        && $injectsByReference->valid()) {
                        foreach ($injectsByReference as $inject) {
                            $resolvedVal = $this->getContainer()->has($inject->getIdentifier())
                                ? $this->getContainer()->get($inject->getIdentifier())
                                : throw new NotFoundException("Definition identifier [{$inject->getIdentifier()}] not found.");

                            $vals = \is_array($resolvedVal) && $parameter->isVariadic() ? $resolvedVal : [$resolvedVal];
                            \array_push($dependencies, ...$vals);
                        }

                        continue;
                    }
                }

                $parameterType = $this->getParameterTypeByReflection($parameter);

                $resolvedVal = null === $parameterType
                    ? $this->getContainer()->get($parameter->getName())
                    : $this->getContainer()->get($parameterType->getName());

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

    abstract public function getContainer(): ContainerInterface;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    protected function resolveArgumentByAttribute(DiAttributeServiceInterface $attribute): mixed
    {
        if (isset($this->resolvedArguments[$attribute->getIdentifier()])) {
            return $this->resolvedArguments[$attribute->getIdentifier()];
        }

        $object = (new DiDefinitionAutowire($attribute->getIdentifier(), $attribute->isSingleton(), $attribute->getArguments()))
            ->setContainer($this->getContainer())
            ->invoke(true)
        ;

        $objectResult = $attribute instanceof DiFactory
            ? $object($this->getContainer())
            : $object;

        return $attribute->isSingleton()
            ? $this->resolvedArguments[$attribute->getIdentifier()] = $objectResult
            : $objectResult;
    }
}
