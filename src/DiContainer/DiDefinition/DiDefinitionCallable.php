<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

use function is_array;
use function is_string;
use function strpos;

use const PHP_VERSION_ID;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 */
final class DiDefinitionCallable implements DiDefinitionArgumentsInterface, DiDefinitionInvokableInterface, DiTaggedDefinitionInterface
{
    use BindArgumentsTrait;
    use CallableParserTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use TagsTrait;

    /**
     * @var NotParsedCallable|ParsedCallable
     */
    private $definition;

    /**
     * @var null|callable
     *
     * @phpstan-var ParsedCallable|null
     */
    private $parsedDefinition;

    /**
     * @var ReflectionParameter[]
     */
    private array $reflectedFunctionParameters;

    /**
     * @param NotParsedCallable|ParsedCallable $definition
     */
    public function __construct(array|callable|string $definition, private ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowireExceptionInterface
     */
    public function invoke(): mixed
    {
        $this->reflectedFunctionParameters ??= $this->reflectParameters();

        if ([] === $this->reflectedFunctionParameters) {
            return $this->getDefinition()();
        }

        return $this->getDefinition()(...$this->resolveParameters($this->getBindArguments(), $this->reflectedFunctionParameters));
    }

    /**
     * @return callable|callable-string
     */
    public function getDefinition(): callable
    {
        return $this->parsedDefinition ??= $this->parseCallable($this->definition); // @phpstan-ignore return.type
    }

    /**
     * @return ReflectionParameter[]
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function reflectParameters(): array
    {
        if (is_array($this->getDefinition())) {
            /**
             * @var non-empty-string|object $class
             * @var non-empty-string        $method
             */
            [$class, $method] = $this->getDefinition();

            return (new ReflectionMethod($class, $method))->getParameters();
        }

        if (is_string($staticMethod = $this->getDefinition()) && strpos($staticMethod, '::') > 0) {
            if (PHP_VERSION_ID >= 80400) {
                // @codeCoverageIgnoreStart
                // @phpstan-ignore method.nonObject, staticMethod.notFound, return.type
                return ReflectionMethod::createFromMethodName($staticMethod)->getParameters();
                // @codeCoverageIgnoreEnd
            }

            return (new ReflectionMethod($this->getDefinition()))->getParameters();
        }

        // @phpstan-return \ReflectionParameter[]
        return (new ReflectionFunction($this->getDefinition())) // @phpstan-ignore argument.type
            ->getParameters()
        ;
    }
}
