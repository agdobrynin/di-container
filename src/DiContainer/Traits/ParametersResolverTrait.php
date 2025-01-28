<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ParametersResolverTrait
{
    use AttributeReaderTrait;
    use ParameterTypeByReflectionTrait;
    use DiContainerTrait;
    use UseAttributeTrait;

    private static int $variadicPosition = 0;

    /**
     * User defined input arguments.
     *
     * @var array<int|string, mixed>
     */
    private array $arguments;

    /**
     * Reflected parameters from function or method.
     *
     * @var \ReflectionParameter[]
     */
    private array $reflectionParameters;

    /**
     * Resolved arguments mark as <isSingleton> by DiAttributeInterface.
     *
     * @phan-suppress PhanReadOnlyPrivateProperty
     *
     * @var array<non-empty-string, mixed>
     */
    private array $resolvedArguments = [];

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @param \ReflectionParameter[] $reflectionParameters
     *
     * @throws AutowireAttributeException
     * @throws AutowireExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function resolveParameters(array $inputArguments, array $reflectionParameters): array
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

        foreach ($this->reflectionParameters as $parameter) {
            if (false !== ($argumentNameOrIndex = $this->getArgumentByNameOrIndex($parameter))) {
                if ($parameter->isVariadic()) {
                    foreach ($this->getInputVariadicArgument($argumentNameOrIndex) as $definitionItem) {
                        $dependencies[] = $this->resolveInputArgument($parameter, $definitionItem);
                    }

                    break; // Variadic Parameter has last position
                }

                $dependencies[] = $this->resolveInputArgument($parameter, $this->arguments[$argumentNameOrIndex]);

                continue;
            }

            $autowireException = null;

            try {
                if ($this->isUseAttribute()
                    && ($attributes = $this->attemptApplyAttributes($parameter))->valid()) {
                    \array_push($dependencies, ...$attributes);

                    continue;
                }

                $parameterType = $this->getParameterTypeByReflection($parameter);

                $dependencies[] = null === $parameterType
                    ? $this->getContainer()->get($parameter->getName())
                    : $this->getContainer()->get($parameterType->getName());

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

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowireExceptionInterface
     */
    private function resolveInputArgument(\ReflectionParameter $parameter, mixed $argumentDefinition): mixed
    {
        if ($argumentDefinition instanceof DiDefinitionGet) {
            return $this->getContainer()->get($argumentDefinition->getDefinition());
        }

        if ($argumentDefinition instanceof DiDefinitionTaggedAsInterface) {
            return $argumentDefinition->setContainer($this->getContainer())
                ->getServicesTaggedAs()
            ;
        }

        if ($argumentDefinition instanceof DiDefinitionInvokableInterface) {
            // Configure definition and invoke definition.
            $argumentDefinition->setContainer($this->getContainer())->setUseAttribute($this->isUseAttribute());
            $object = ($o = $argumentDefinition->invoke()) instanceof DiFactoryInterface
                ? $o($this->getContainer())
                : $o;

            if ($argumentDefinition->isSingleton()) {
                $identifier = \sprintf('%s:%s', $parameter->getDeclaringFunction()->getName(), $parameter->getName());

                if ($parameter->isVariadic()) {
                    $identifier .= \sprintf('#%d', self::$variadicPosition++);
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
    private function attemptApplyAttributes(\ReflectionParameter $parameter): \Generator
    {
        $injects = $this->getInjectAttribute($parameter);
        $asClosures = $this->getProxyClosureAttribute($parameter);
        $taggedAs = $this->getTaggedAsAttribute($parameter);

        if (!$injects->valid() && !$asClosures->valid() && !$taggedAs->valid()) {
            return;
        }

        if (($injects->valid() xor $asClosures->valid() xor $taggedAs->valid())
            && !($injects->valid() && $asClosures->valid() && $taggedAs->valid())) {
            if ($injects->valid()) {
                foreach ($injects as $inject) {
                    yield $inject->getIdentifier()
                        ? $this->getContainer()->get($inject->getIdentifier())
                        : $this->getContainer()->get($parameter->getName());
                }

                return;
            }

            if ($asClosures->valid()) {
                foreach ($asClosures as $asClosure) {
                    yield $this->resolveInputArgument(
                        $parameter,
                        new DiDefinitionProxyClosure($asClosure->getIdentifier(), $asClosure->isSingleton())
                    );
                }

                return;
            }

            foreach ($taggedAs as $tagged) {
                yield (new DiDefinitionTaggedAs($tagged->getIdentifier(), $tagged->isLazy()))
                    ->setContainer($this->getContainer())
                    ->getServicesTaggedAs()
                ;
            }

            return;
        }

        throw new AutowireAttributeException(
            \sprintf(
                'Only one of the attributes #[%s], #[%s] or #[%s] must be declared.',
                Inject::class,
                ProxyClosure::class,
                TaggedAs::class
            )
        );
    }

    private function getInputVariadicArgument(int|string $argumentNameOrIndex): array
    {
        if (\is_string($argumentNameOrIndex)) {
            return \is_array($this->arguments[$argumentNameOrIndex])
                ? $this->arguments[$argumentNameOrIndex]
                : [$this->arguments[$argumentNameOrIndex]];
        }

        return \array_slice($this->arguments, $argumentNameOrIndex);
    }

    /**
     * @throws AutowireExceptionInterface
     */
    private function validateInputArguments(): void
    {
        if ([] !== $this->arguments) {
            $parameters = \array_column($this->reflectionParameters, 'name');
            $hasVariadic = [] !== \array_filter($this->reflectionParameters, static fn (\ReflectionParameter $parameter) => $parameter->isVariadic());

            if (!$hasVariadic && \count($this->arguments) > \count($parameters)) {
                throw new AutowireException(
                    \sprintf(
                        'Too many input arguments. Input index or name arguments "%s". Definition parameters: %s',
                        \implode(', ', \array_keys($this->arguments)),
                        ($p = \implode(', ', $parameters)) ? '"'.$p.'"' : 'without input parameters'
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

    private function getArgumentByNameOrIndex(\ReflectionParameter $parameter): false|int|string
    {
        if ([] === $this->arguments) {
            return false;
        }

        return match (true) {
            \array_key_exists($parameter->name, $this->arguments) => $parameter->name,
            \array_key_exists($parameter->getPosition(), $this->arguments) => $parameter->getPosition(),
            default => false,
        };
    }
}
