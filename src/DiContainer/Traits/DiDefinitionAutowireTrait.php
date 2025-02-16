<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;

trait DiDefinitionAutowireTrait
{
    /**
     * @param non-empty-string                          $where
     * @param array<non-negative-int, non-empty-string> $supportReturnTypes
     */
    private static function callStaticMethod(DiDefinitionAutowireInterface $definition, mixed $method, bool $requireMethod, string $where, array $supportReturnTypes = ['int', 'string', 'null'], mixed ...$args): mixed
    {
        if (!\is_string($method) || '' === \trim($method)) {
            throw new AutowireException($where.' The value option must be non-empty string.');
        }

        // @phpstan-var callable $callableExpression
        $callableExpression = [$definition->getDefinition()->name, $method];
        $isCallable = \is_callable($callableExpression);

        // @phpstan-ignore argument.type
        if (!$isCallable || [] !== ($types = static::diffReturnType($definition->getDefinition()->getMethod($method)->getReturnType(), ...$supportReturnTypes))) {
            if (!$requireMethod) {
                return null;
            }

            $message = \sprintf(
                '%s "%s::%s()" method must be declared with public and static modifiers. Return type must be %s.%s',
                $where,
                $definition->getDefinition()->name,
                $method,
                \implode(', ', $supportReturnTypes),
                isset($types) ? ' Got return type: '.\implode(', ', $types) : ''
            );

            throw new AutowireException($message);
        }

        // @phpstan-ignore return.type
        return \call_user_func($callableExpression, ...$args);
    }

    /**
     * @return array<string>
     */
    private static function diffReturnType(null|\ReflectionNamedType|\ReflectionUnionType $rt, string ...$type): array
    {
        $fn = static fn (\ReflectionNamedType $t): string => $t->getName();

        $types = match (true) {
            $rt instanceof \ReflectionNamedType => [$rt->getName()],
            $rt instanceof \ReflectionUnionType => \array_map($fn, $rt->getTypes()), // @phpstan-ignore argument.type
            default => ['undefined'],
        };

        return \array_diff($types, $type);
    }
}
