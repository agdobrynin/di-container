<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ParametersResolverTrait
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;
    use PsrContainerTrait;
    use UseAttributeTrait;

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
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function resolveParameters(): array
    {
        $dependencies = [];

        foreach ($this->reflectionParameters as $parameter) {
            if (\array_key_exists($parameter->name, $this->arguments)) {
                $argumentDefinition = $this->arguments[$parameter->name];

                if (\is_array($argumentDefinition) && $parameter->isVariadic()) {
                    foreach ($argumentDefinition as $definitionItem) {
                        $resolvedVal = $this->resolveUserDefinedArgument($parameter, $definitionItem);
                        $dependencies[] = $resolvedVal;
                    }

                    continue;
                }

                $resolvedVal = $this->resolveUserDefinedArgument($parameter, $argumentDefinition);

                $vals = \is_array($resolvedVal) && $parameter->isVariadic()
                    ? $resolvedVal
                    : [$resolvedVal];

                \array_push($dependencies, ...$vals);

                continue;
            }

            $autowireException = null;

            try {
                if ($this->isUseAttribute() && ($injectAttribute = $this->getInjectAttribute($parameter))
                    && $injectAttribute->valid()) {
                    foreach ($injectAttribute as $inject) {
                        $resolvedVal = $inject->getIdentifier()
                            ? $this->getContainer()->get($inject->getIdentifier())
                            : $this->getContainer()->get($parameter->getName());

                        $vals = \is_array($resolvedVal) && $parameter->isVariadic()
                            ? $resolvedVal
                            : [$resolvedVal];
                        \array_push($dependencies, ...$vals);
                    }

                    continue;
                }

                $parameterType = $this->getParameterTypeByReflection($parameter);

                $resolvedVal = null === $parameterType
                    ? $this->getContainer()->get($parameter->getName())
                    : $this->getContainer()->get($parameterType->getName());

                $vals = \is_array($resolvedVal) && $parameter->isVariadic()
                    ? $resolvedVal
                    : [$resolvedVal];
                \array_push($dependencies, ...$vals);

                continue;
            } catch (AutowiredAttributeException|CallCircularDependencyException $e) {
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
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    protected function resolveUserDefinedArgument(\ReflectionParameter $parameter, mixed $argumentDefinition): mixed
    {
        if ($argumentDefinition instanceof DiDefinitionReference) {
            return $this->getContainer()->get($argumentDefinition->getDefinition());
        }

        if ($argumentDefinition instanceof DiDefinitionAutowireInterface) {
            // @todo move logic from self::resolveContextArgument to here. Move invoke object to trait.
            $argumentDefinition->setContainer($this->getContainer())
                ->setUseAttribute($this->isUseAttribute())
            ;

            return $this->resolveContextArgument($parameter, $argumentDefinition);
        }

        if ($argumentDefinition instanceof DiDefinitionInterface) {
            return $argumentDefinition->getDefinition();
        }

        return $argumentDefinition;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    protected function resolveContextArgument(\ReflectionParameter $parameter, DiDefinitionAutowireInterface $definitionAutowire): mixed
    {
        static $variadicPosition = 0;

        $id = $parameter->isVariadic()
            ? \sprintf('%s#%d', $parameter->getName(), ++$variadicPosition)
            : $parameter->getName();

        if (isset($this->resolvedArguments[$id])) {
            return $this->resolvedArguments[$id];
        }

        $object = $definitionAutowire->invoke();

        $objectResult = $object instanceof DiFactoryInterface
            ? $object($this->getContainer())
            : $object;

        return $definitionAutowire->isSingleton()
            ? $this->resolvedArguments[$id] = $objectResult
            : $objectResult;
    }
}
