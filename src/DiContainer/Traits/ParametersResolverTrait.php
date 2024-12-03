<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
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

    protected static int $variadicPosition = 0;

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
     * @var array<int|string, mixed>
     */
    protected array $arguments = [];

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function addArgument(int|string $name, mixed $value): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);

        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     */
    public function addArguments(array $arguments): static
    {
        @\trigger_error('Use method bindArguments(). This method will remove next major release.', \E_USER_DEPRECATED);
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @phan-suppress PhanTypeMismatchReturn
     */
    public function bindArguments(mixed ...$argument): static
    {
        $this->arguments = $argument;

        return $this;
    }

    /**
     * @throws AutowireAttributeException
     * @throws AutowireExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function resolveParameters(): array
    {
        // Check valid user defined arguments
        $this->validateInputArguments();

        $dependencies = [];
        self::$variadicPosition = 0;

        foreach ($this->reflectionParameters as $parameter) {
            if (false !== ($argumentNameOrIndex = $this->getArgumentNameOrIndexByParameter($parameter))) {
                $argumentDefinition = $this->getInputArgument($argumentNameOrIndex, $parameter->isVariadic());

                if (\is_array($argumentDefinition) && $parameter->isVariadic()) {
                    foreach ($argumentDefinition as $definitionItem) {
                        $resolvedVal = $this->resolveInputArgument($parameter, $definitionItem);
                        $dependencies[] = $resolvedVal;
                    }

                    continue;
                }

                $resolvedVal = $this->resolveInputArgument($parameter, $argumentDefinition);

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
            } catch (AutowireAttributeException|CallCircularDependencyException $e) {
                throw $e;
            } catch (AutowireExceptionInterface|ContainerExceptionInterface $e) {
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

            throw new AutowireException(message: $message, previous: $autowireException);
        }

        return $dependencies;
    }

    abstract public function getContainer(): ContainerInterface;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowireExceptionInterface
     */
    protected function resolveInputArgument(\ReflectionParameter $parameter, mixed $argumentDefinition): mixed
    {
        if ($argumentDefinition instanceof DiDefinitionGet) {
            return $this->getContainer()->get($argumentDefinition->getDefinition());
        }

        if ($argumentDefinition instanceof DiDefinitionAutowireInterface) {
            $id = $parameter->isVariadic()
                ? \sprintf('%s#%d', $parameter->getName(), self::$variadicPosition++)
                : $parameter->getName();

            if (isset($this->resolvedArguments[$id])) {
                return $this->resolvedArguments[$id];
            }

            // Configure definition.
            $argumentDefinition->setContainer($this->getContainer())
                ->setUseAttribute($this->isUseAttribute())
            ;

            $objectResult = ($o = $argumentDefinition->invoke()) instanceof DiFactoryInterface
                ? $o($this->getContainer())
                : $o;

            return $argumentDefinition->isSingleton()
                ? $this->resolvedArguments[$id] = $objectResult
                : $objectResult;
        }

        if ($argumentDefinition instanceof DiDefinitionInterface) {
            return $argumentDefinition->getDefinition();
        }

        return $argumentDefinition;
    }

    /**
     * @throws AutowireExceptionInterface
     */
    protected function validateInputArguments(): void
    {
        if ([] !== $this->arguments) {
            $parameters = \array_column($this->reflectionParameters, 'name');
            $hasVariadic = [] !== \array_filter($this->reflectionParameters, static fn (\ReflectionParameter $parameter) => $parameter->isVariadic());

            if (!$hasVariadic && \count($this->arguments) > \count($parameters)) {
                throw new AutowireAttributeException(
                    \sprintf(
                        'Too many input arguments "%s". Definition '.__CLASS__.' has arguments: "%s"',
                        \implode(', ', \array_keys($this->arguments)),
                        \implode(', ', $parameters)
                    )
                );
            }

            $argumentPosition = 0;

            foreach ($this->arguments as $name => $value) {
                ++$argumentPosition;

                if (\is_string($name) && !\in_array($name, $parameters, true)) {
                    throw new AutowireAttributeException(
                        \sprintf(
                            'Invalid input argument name "%s" at position #%d. Definition '.__CLASS__.' has arguments: "%s"',
                            $name,
                            $argumentPosition,
                            \implode(', ', $parameters)
                        )
                    );
                }
            }
        }
    }

    protected function getArgumentNameOrIndexByParameter(\ReflectionParameter $parameter): false|int|string
    {
        return match (true) {
            [] === $this->arguments => false,
            \array_key_exists($parameter->name, $this->arguments) => $parameter->name,
            \array_key_exists($parameter->getPosition(), $this->arguments) => $parameter->getPosition(),
            default => false,
        };
    }

    protected function getInputArgument(int|string $argumentNameOrIndex, bool $isVariadic): mixed
    {
        if (\is_string($argumentNameOrIndex)) {
            return $this->arguments[$argumentNameOrIndex];
        }

        if (!$isVariadic || !\array_key_exists($argumentNameOrIndex + 1, $this->arguments)) {
            return $this->arguments[$argumentNameOrIndex];
        }

        $values = [];
        $variadicPosition = $argumentNameOrIndex;

        do {
            $values[] = $this->arguments[$variadicPosition];
            ++$variadicPosition;
        } while (\array_key_exists($variadicPosition, $this->arguments));

        return $values;
    }
}
