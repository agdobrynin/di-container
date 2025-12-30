<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;
use function var_export;

final class ProxyClosureEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionProxyClosureInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $identifier = $this->definition->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException('Cannot compile definition. Container identifier is invalid.', previous: $e);
        }

        if (!$this->diContainerDefinitions->getContainer()->has($identifier)) {
            throw new DefinitionCompileException(
                sprintf('Cannot compile definition. Entry not found via container identifier "%s".', $identifier)
            );
        }

        return new CompiledEntry(
            isSingleton: $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault(),
            expression: sprintf('fn () => %s->get(%s)', $containerVar, var_export($identifier, true)),
            returnType: '\Closure',
        );
    }

    public function getDiDefinition(): DiDefinitionProxyClosureInterface
    {
        return $this->definition;
    }
}
