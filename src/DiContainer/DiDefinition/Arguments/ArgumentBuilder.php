<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition\Arguments;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\ArgumentBuilderException;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;

use function array_column;
use function array_filter;
use function array_key_exists;
use function array_push;
use function array_slice;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
final class ArgumentBuilder implements ArgumentBuilderInterface
{
    /**
     * @param BindArgumentsType $bindArguments
     */
    public function __construct(
        private readonly array $bindArguments,
        private readonly ReflectionFunctionAbstract $functionOrMethod,
        private readonly DiContainerInterface $container,
    ) {}

    public function getBindArguments(): array
    {
        return $this->bindArguments;
    }

    public function getFunctionOrMethod(): ReflectionFunctionAbstract
    {
        return $this->functionOrMethod;
    }

    public function getContainer(): DiContainerInterface
    {
        return $this->container;
    }

    public function build(): array
    {
        return $this->container->getConfig()->isUseAttribute()
            ? $this->basedOnPhpAttributes()
            : $this->basedOnBindArguments();
    }

    public function buildByPriorityBindArguments(): array
    {
        return $this->container->getConfig()->isUseAttribute()
            ? $this->basedOnBindArgumentsAsPriorityAndPhpAttributes()
            : $this->basedOnBindArguments();
    }

    /**
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderException
     */
    private function basedOnBindArguments(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $param) {
            if (false !== $this->pushFromBindArguments($args, $param)) {
                continue;
            }

            $this->pushFromParameterType($args, $param);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderException
     */
    private function basedOnPhpAttributes(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $param) {
            try {
                if (($definitions = $this->getDefinitionByAttributes($param))->valid()) {
                    array_push($args, ...$definitions);

                    continue;
                }
            } catch (AutowireAttributeException|AutowireParameterTypeException $e) {
                throw new ArgumentBuilderException(
                    message: sprintf('Cannot build argument via php attribute for %s in %s.', $param, Helper::functionName($param->getDeclaringFunction())),
                    previous: $e
                );
            }

            if (false !== $this->pushFromBindArguments($args, $param)) {
                continue;
            }

            $this->pushFromParameterType($args, $param);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderException
     */
    private function basedOnBindArgumentsAsPriorityAndPhpAttributes(): array
    {
        $args = [];

        foreach ($this->functionOrMethod->getParameters() as $param) {
            if (false !== $this->pushFromBindArguments($args, $param)) {
                continue;
            }

            try {
                if (($definitions = $this->getDefinitionByAttributes($param))->valid()) {
                    array_push($args, ...$definitions);

                    continue;
                }
            } catch (AutowireAttributeException|AutowireParameterTypeException $e) {
                throw new ArgumentBuilderException(
                    message: sprintf('Cannot build argument via php attribute for %s in %s.', $param, Helper::functionName($param->getDeclaringFunction())),
                    previous: $e
                );
            }

            $this->pushFromParameterType($args, $param);
        }

        array_push($args, ...$this->getTailArguments());

        return $args;
    }

    /**
     * @param BindArgumentsType $args
     *
     * @throws ArgumentBuilderException
     */
    private function pushFromParameterType(array &$args, ReflectionParameter $param): void
    {
        if ($param->isDefaultValueAvailable()) {
            return;
        }

        try {
            $strType = Helper::getParameterTypeHint($param, $this->container);

            if (!$this->container->has($strType)) {
                throw new NotFoundException(id: $strType);
            }

            $args[$param->getPosition()] = new DiDefinitionGet($strType); // @phpstan-ignore parameterByRef.type
        } catch (AutowireParameterTypeException|NotFoundExceptionInterface $e) {
            throw new ArgumentBuilderException(
                message: sprintf('Cannot build argument via type hint for %s in %s.', $param, Helper::functionName($param->getDeclaringFunction())),
                previous: $e
            );
        }
    }

    /**
     * @param BindArgumentsType $args
     *
     * @return bool when argument found return true
     */
    private function pushFromBindArguments(array &$args, ReflectionParameter $param): bool
    {
        $argNameOrIndex = match (true) {
            array_key_exists($param->name, $this->bindArguments) => $param->name,
            array_key_exists($param->getPosition(), $this->bindArguments) => $param->getPosition(),
            default => false,
        };

        if (false !== $argNameOrIndex) {
            if ($param->isVariadic()) {
                foreach ($this->capturingVariadicArguments($argNameOrIndex) as $argKey => $definition) {
                    $args[$argKey] = $definition;
                }

                return true; // Variadic Parameter has last position
            }

            // @phpstan-ignore parameterByRef.type
            $args[$param->getPosition()] = $this->bindArguments[$argNameOrIndex];

            return true;
        }

        /*
         * Even if the binding argument is not found by position or named argument,
         * it is possible to pass a named argument by any name.
         */
        if ($param->isVariadic()) {
            foreach ($this->capturingVariadicArguments($param->name) as $argKey => $definition) {
                $args[$argKey] = $definition;
            }

            return true; // Variadic Parameter has last position
        }

        return false;
    }

    /**
     * Add unused bind arguments.
     * This can be useful for functions without arguments or tail argument
     * that use functions like `func_get_args()` or any `func_*()`.
     *
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderException
     */
    private function getTailArguments(): array
    {
        if (!$this->functionOrMethod->isVariadic()
            && (count($this->bindArguments) > ($c = count($this->functionOrMethod->getParameters())))) {
            $tailArgs = array_slice($this->bindArguments, $c, preserve_keys: true);

            foreach ($tailArgs as $key => $value) {
                if (is_string($key)) {
                    throw new ArgumentBuilderException(
                        sprintf('Cannot build arguments for %s. Does not accept unknown named parameter $%s.', Helper::functionName($this->functionOrMethod), $key)
                    );
                }
            }

            return $tailArgs;
        }

        return [];
    }

    /**
     * @return BindArgumentsType
     */
    private function capturingVariadicArguments(int|string $argumentNameOrIndex): array
    {
        if (is_int($argumentNameOrIndex)) {
            return array_slice($this->bindArguments, $argumentNameOrIndex, preserve_keys: true);
        }

        $paramNames = array_column($this->functionOrMethod->getParameters(), 'name');

        return array_filter(
            $this->bindArguments,
            static fn (int|string $nameOrIndex) => !in_array($nameOrIndex, $paramNames, true) || $nameOrIndex === $argumentNameOrIndex,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return Generator<(DiDefinitionCallable|DiDefinitionFactory|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs)>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException
     */
    private function getDefinitionByAttributes(ReflectionParameter $param): Generator
    {
        $attrs = AttributeReader::getAttributeOnParameter($param, $this->container);

        if (!$attrs->valid()) {
            return;
        }

        foreach ($attrs as $attr) {
            if ($attr instanceof Inject) {
                $definition = new DiDefinitionGet($attr->id); // @phpstan-ignore argument.type
            } elseif ($attr instanceof ProxyClosure) {
                $definition = new DiDefinitionProxyClosure($attr->id);
            } elseif ($attr instanceof TaggedAs) {
                $definition = new DiDefinitionTaggedAs(
                    $attr->name,
                    $attr->isLazy,
                    $attr->priorityDefaultMethod,
                    $attr->useKeys,
                    $attr->key,
                    $attr->keyDefaultMethod,
                    $attr->containerIdExclude,
                    $attr->selfExclude,
                );
            } elseif ($attr instanceof DiFactory) {
                $definition = (new DiDefinitionFactory($attr->definition))
                    ->bindArguments(...$attr->arguments)
                ;
            } else {
                $definition = new DiDefinitionCallable($attr->getCallable());
            }

            yield $definition;
        }
    }
}
