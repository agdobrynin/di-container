<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Traits\ArgumentResolverTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunction;
use ReflectionMethod;

use function call_user_func_array;
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
    use BindArgumentsTrait {
        bindArguments as private bindArgs;
    }
    use CallableParserTrait;
    use ArgumentResolverTrait;
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

    private ArgumentBuilder $argBuilder;

    private ReflectionFunction|ReflectionMethod $reflectionFn;

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

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgs(...$argument);
        unset($this->argBuilder);

        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AutowireExceptionInterface
     */
    public function invoke(): mixed
    {
        $this->reflectionFn ??= $this->reflectionFn();
        $this->argBuilder ??= new ArgumentBuilder($this->getBindArguments(), $this->reflectionFn, $this->getContainer());
        $args = (bool) $this->getContainer()->getConfig()?->isUseAttribute()
            ? $this->argBuilder->basedOnPhpAttributes()
            : $this->argBuilder->basedOnBindArguments();
        $resolvedArgs = $this->resolveArguments($args);

        return call_user_func_array($this->getDefinition(), $resolvedArgs);
    }

    /**
     * @throws ContainerExceptionInterface|DiDefinitionCallableExceptionInterface|NotFoundExceptionInterface
     */
    public function getDefinition(): callable
    {
        return $this->parsedDefinition ??= $this->parseCallable($this->definition); // @phpstan-ignore return.type
    }

    /**
     * @throws ContainerExceptionInterface|DiDefinitionCallableExceptionInterface|NotFoundExceptionInterface
     */
    private function reflectionFn(): ReflectionFunction|ReflectionMethod
    {
        if (is_array($this->getDefinition())) {
            /**
             * @var non-empty-string|object $class
             * @var non-empty-string        $method
             */
            [$class, $method] = $this->getDefinition();

            return new ReflectionMethod($class, $method);
        }

        if (is_string($staticMethod = $this->getDefinition()) && strpos($staticMethod, '::') > 0) {
            if (PHP_VERSION_ID >= 80400) {
                // @codeCoverageIgnoreStart
                // @phpstan-ignore staticMethod.notFound, return.type
                return ReflectionMethod::createFromMethodName($staticMethod);
                // @codeCoverageIgnoreEnd
            }

            return new ReflectionMethod($this->getDefinition());
        }

        // @phpstan-ignore argument.type
        return new ReflectionFunction($this->getDefinition());
    }
}
