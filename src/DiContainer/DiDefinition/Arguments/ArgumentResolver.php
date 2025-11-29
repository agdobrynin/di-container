<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition\Arguments;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

use function array_key_exists;
use function is_int;
use function sprintf;

/**
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
final class ArgumentResolver
{
    /**
     * @return mixed[]
     *
     * @throws ArgumentBuilderExceptionInterface|DiDefinitionExceptionInterface
     */
    public static function resolve(ArgumentBuilderInterface $argBuilder, DiContainerInterface $container, ?DiDefinitionInterface $context = null): array
    {
        return self::resolveArgs($argBuilder->build(), $argBuilder, $container, $context);
    }

    /**
     * @return mixed[]
     *
     * @throws ArgumentBuilderExceptionInterface|DiDefinitionExceptionInterface
     */
    public static function resolveByPriorityBindArguments(ArgumentBuilderInterface $argBuilder, DiContainerInterface $container, ?DiDefinitionInterface $context = null): array
    {
        return self::resolveArgs($argBuilder->buildByPriorityBindArguments(), $argBuilder, $container, $context);
    }

    /**
     * @param BindArgumentsType $args
     *
     * @return mixed[]
     *
     * @throws DiDefinitionExceptionInterface
     */
    private static function resolveArgs(array $args, ArgumentBuilderInterface $argBuilder, DiContainerInterface $container, ?DiDefinitionInterface $context): array
    {
        $resolvedArgs = [];

        foreach ($args as $argNameOrIndex => $arg) {
            try {
                $resolvedArgs[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                    ? $arg->resolve($container, $context)
                    : $arg;
            } catch (ContainerExceptionInterface $e) {
                if (is_int($argNameOrIndex)) {
                    $param = $argBuilder->getFunctionOrMethod()->getParameters()[$argNameOrIndex] ?? null;
                    $argPresentedBy = null !== $param && array_key_exists($param->getName(), $argBuilder->getBindArguments())
                        ? $param->getName()
                        : $argNameOrIndex;
                } else {
                    $argPresentedBy = $argNameOrIndex;
                }

                $argMessage = is_int($argPresentedBy)
                    ? sprintf('at position #%d', $argPresentedBy)
                    : sprintf('by named argument $%s', $argPresentedBy);

                throw (
                    new DiDefinitionException(
                        message: sprintf('Cannot resolve parameter %s in %s.', $argMessage, Helper::functionName($argBuilder->getFunctionOrMethod())),
                        previous: $e
                    )
                )
                    ->setContext(context_argument: $arg, context_fn_reflection: $argBuilder->getFunctionOrMethod())
                ;
            }
        }

        return $resolvedArgs;
    }
}
