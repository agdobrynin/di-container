<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use ReflectionNamedType;
use ReflectionUnionType;

use function array_diff;
use function array_map;
use function implode;
use function is_callable;
use function is_string;
use function sprintf;
use function trim;

trait DiDefinitionAutowireTrait
{
    /**
     * @param non-empty-string                          $where
     * @param array<non-negative-int, non-empty-string> $supportReturnTypes
     */
    private static function callStaticMethod(DiDefinitionAutowireInterface $definition, mixed $method, bool $requireMethod, string $where, array $supportReturnTypes = ['int', 'string', 'null'], mixed ...$args): mixed
    {
        if (!is_string($method) || '' === trim($method)) {
            throw new AutowireException($where.' The value option must be non-empty string.');
        }

        $isCallable = is_callable([$definition->getDefinition()->name, $method]);

        // @phpstan-ignore argument.type
        if (!$isCallable || [] !== ($types = static::diffReturnType($definition->getDefinition()->getMethod($method)->getReturnType(), ...$supportReturnTypes))) {
            if (!$requireMethod) {
                return null;
            }

            $message = sprintf(
                '%s "%s::%s()" method must be exist and be declared with the public and static modifiers. Return type must be %s.%s',
                $where,
                $definition->getDefinition()->name,
                $method,
                '"'.implode('", "', $supportReturnTypes).'"',
                isset($types) ? ' Got return type: "'.implode('", "', $types).'".' : ''
            );

            throw new AutowireException($message);
        }

        // @phpstan-ignore return.type, staticMethod.dynamicName
        return $definition->getDefinition()->name::$method(...$args);
    }

    /**
     * @return array<string>
     */
    private static function diffReturnType(null|ReflectionNamedType|ReflectionUnionType $returnType, string ...$type): array
    {
        $fn = static fn (ReflectionNamedType $t): string => $t->getName();

        $types = match (true) {
            $returnType instanceof ReflectionNamedType => [$returnType->getName()],
            $returnType instanceof ReflectionUnionType => array_map($fn, $returnType->getTypes()), // @phpstan-ignore argument.type
            default => ['undefined'],
        };

        return array_diff($types, $type);
    }
}
