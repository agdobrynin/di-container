<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait CallableParserTrait
{
    use PsrContainerTrait;

    abstract public function getContainer(): ContainerInterface;

    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function parseCallable(array|callable|string $definition): callable
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

        throw new DiDefinitionCallableException('Definition is not callable. Got: '.\var_export($definition, true));
    }

    private function parseDefinitions(array|string $argument): array
    {
        if (\is_array($argument)) {
            if (!isset($argument[0], $argument[1])) {
                throw new DiDefinitionCallableException('When the definition is an array, two array elements must be provided. Got: '.\var_export($argument, true));
            }

            return [$argument[0], $argument[1]];
        }

        if (\strpos($argument, '::') > 0) {
            return \explode('::', $argument, 2);
        }

        return [$argument, '__invoke'];
    }
}
