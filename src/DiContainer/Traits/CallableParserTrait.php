<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function explode;
use function get_debug_type;
use function is_array;
use function is_callable;
use function is_string;
use function sprintf;
use function str_contains;
use function var_export;

/**
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 */
trait CallableParserTrait
{
    use DiContainerTrait;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @phpstan-param NotParsedCallable|ParsedCallable $definition
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function parseCallable(array|callable|string $definition): callable
    {
        if (is_callable($definition)) {
            return $definition;
        }

        $parsedDefinition = $this->parseDefinitions($definition);

        if (is_string($containerIdentifier = $parsedDefinition[0])
            && $this->getContainer()->has($containerIdentifier)) {
            $parsedDefinition[0] = $this->getContainer()->get($containerIdentifier);
        }

        if (is_callable($parsedDefinition)) {
            return $parsedDefinition;
        }

        throw new DiDefinitionCallableException(
            sprintf('Definition is not callable. Got: type "%s", value: %s.', get_debug_type($definition), var_export($definition, true))
        );
    }

    /**
     * @phpstan-param  NotParsedCallable $argument
     *
     * @return array{0: non-empty-string|object, 1: non-empty-string}
     */
    private function parseDefinitions(array|string $argument): array
    {
        if (is_array($argument)) {
            if (!isset($argument[0], $argument[1])) {
                throw new DiDefinitionCallableException(
                    sprintf('When the definition is an array, two array elements must be provided. Got: type: "%s", value %s.', get_debug_type($argument), var_export($argument, true))
                );
            }

            return [$argument[0], $argument[1]];
        }

        if (str_contains($argument, '::')) {
            /** @var array{0: non-empty-string, 1: non-empty-string} $classStaticMethod */
            $classStaticMethod = [$class, $method] = explode('::', $argument, 2);

            if ('' === $class || '' === $method) {
                throw new DiDefinitionCallableException(
                    sprintf('Wrong callable definition present. Got: "%s"', $argument)
                );
            }

            return $classStaticMethod;
        }

        return [$argument, '__invoke'];
    }
}
