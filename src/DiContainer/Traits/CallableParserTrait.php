<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait CallableParserTrait
{
    use DiContainerTrait;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @param callable|non-empty-list<non-empty-string|object>|non-empty-string $definition
     *
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function parseCallable(array|callable|string $definition): callable
    {
        if (\is_callable($definition)) {
            return $definition;
        }

        $parsedDefinition = $this->parseDefinitions($definition);

        if (\is_string($parsedDefinition[0])) {
            $parsedDefinition[0] = $this->getContainer()->get($parsedDefinition[0]);
        }

        if (\is_callable($parsedDefinition)) {
            return $parsedDefinition;
        }

        throw new DiDefinitionCallableException(
            \sprintf('Definition is not callable. Got: %s', \var_export($definition, true))
        );
    }

    /**
     * @param list<non-empty-string|object>|non-empty-string $argument
     *
     * @return non-empty-list<non-empty-string|object>
     */
    private function parseDefinitions(array|string $argument): array
    {
        if (\is_array($argument)) {
            if (!isset($argument[0], $argument[1])) {
                throw new DiDefinitionCallableException(
                    \sprintf('When the definition is an array, two array elements must be provided. Got: %s', \var_export($argument, true))
                );
            }

            return [$argument[0], $argument[1]];
        }

        if (\strpos($argument, '::') > 0) {
            /** @var non-empty-list<non-empty-string> $r */
            $r = [$c, $m] = \explode('::', $argument, 2);

            if ('' === $c || '' === $m) {
                throw new DiDefinitionCallableException(
                    \sprintf('Wrong callable definition present. Got: %s', $argument)
                );
            }

            return $r;
        }

        return [$argument, '__invoke'];
    }
}
