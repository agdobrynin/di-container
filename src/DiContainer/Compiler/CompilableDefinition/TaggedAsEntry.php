<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;
use function var_export;

use const PHP_EOL;

final class TaggedAsEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionTaggedAsInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $mapContainerIdentifiers = $this->definition->exposeContainerIdentifiers(
                $this->diContainerDefinitions->getContainer(),
                $context
            );
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot provide container identifiers for tag "%s".', $this->getDiDefinition()->getDefinition()),
                previous: $e
            );
        }

        if ($this->definition->isLazy()) {
            // build map tagged key => container identifier
            $expressionIds = '['.PHP_EOL;

            foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
                $expressionIds .= sprintf('  %s => %s,'.PHP_EOL, var_export($key, true), var_export($containerIdentifier, true));
            }

            $expressionIds .= ']';

            $expression = sprintf('/* Lazy load services for tag %s */'.PHP_EOL, var_export($this->definition->getDefinition(), true));
            $expression .= sprintf('new \Kaspi\DiContainer\LazyDefinitionIterator(%s, %s)', $containerVar, $expressionIds);

            return new CompiledEntry(
                isSingleton: false,
                expression: $expression,
                returnType: '\Kaspi\DiContainer\LazyDefinitionIterator',
            );
        }

        $expression = sprintf('/* Services for tag %s */'.PHP_EOL, var_export($this->definition->getDefinition(), true));
        $expression .= '['.PHP_EOL;

        foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
            $expression .= sprintf('  %s => %s->get(%s),'.PHP_EOL, var_export($key, true), $containerVar, var_export($containerIdentifier, true));
        }

        $expression .= ']';

        return new CompiledEntry(
            isSingleton: false,
            expression: $expression,
            returnType: 'array',
        );
    }

    public function getDiDefinition(): DiDefinitionTaggedAsInterface
    {
        return $this->definition;
    }
}
