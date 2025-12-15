<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\DefinitionCompiler;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;
use function var_export;

final class GetViaProxyClosure implements CompilableDefinitionInterface
{
    public function __construct(private readonly DiDefinitionProxyClosure $definition, private readonly DiContainerInterface $container) {}

    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $identifier = $this->definition->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException('Cannot compile definition. Container identifier is invalid.', previous: $e);
        }

        if (!$this->container->has($identifier)) {
            throw new DiDefinitionException(sprintf('Cannot compile definition. Entry not found via container identifier "%s".', $identifier));
        }

        $expression = sprintf('fn () => %s->get(%s)', $containerVariableName, var_export($identifier, true));
        $isSingleton = $this->definition->isSingleton() ?? $this->container->getConfig()->isSingletonServiceDefault();

        return new CompiledEntry($expression, $isSingleton, returnType: '\Closure');
    }

    public function getDiDefinition(): DiDefinitionProxyClosure
    {
        return $this->definition;
    }
}
