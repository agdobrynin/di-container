<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\DefinitionCompiler;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;
use function var_export;

final class TaggedAs implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionTaggedAsInterface $definition,
        private readonly DiContainerInterface $container
    ) {}

    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $mapContainerIdentifiers = $this->definition->exposeContainerIdentifiers($this->container, $context);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot compile tagged services. Tag "%s".', $this->getDiDefinition()->getDefinition()),
                previous: $e
            );
        }

        $isSingleton = false;

        if ($this->definition instanceof DiDefinitionSingletonInterface) {
            $isSingleton = $this->definition->isSingleton() ?? $this->container->getConfig()->isSingletonServiceDefault();
        }

        if ($this->definition->isLazy()) {
            // build map tagged key => container identifier
            $ids = '['.PHP_EOL;

            foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
                $ids .= sprintf('  %s => %s,'.PHP_EOL, var_export($key, true), var_export($containerIdentifier, true));
            }

            $ids .= ']';

            $comment = sprintf('/* Lazy load services for tag %s */', var_export($this->definition->getDefinition(), true));
            $expression = sprintf('new \Kaspi\DiContainer\LazyDefinitionIterator(%s, %s)', $containerVariableName, $ids);

            return new CompiledEntry($expression, $isSingleton, $comment, returnType: '\Kaspi\DiContainer\LazyDefinitionIterator');
        }

        $expression = '['.PHP_EOL;

        foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
            $expression .= sprintf('  %s => %s->get(%s),'.PHP_EOL, var_export($key, true), $containerVariableName, var_export($containerIdentifier, true));
        }

        $expression .= ']';

        $comment = sprintf('/* Services for tag %s */', var_export($this->definition->getDefinition(), true));

        return new CompiledEntry($expression, $isSingleton, $comment, returnType: 'array');
    }

    public function getDiDefinition(): DiDefinitionTaggedAsInterface
    {
        return $this->definition;
    }
}
