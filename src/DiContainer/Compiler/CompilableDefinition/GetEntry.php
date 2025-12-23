<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerInterface;

use function in_array;
use function sprintf;
use function var_export;

final class GetEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionLinkInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $containerIdentifier = $this->definition->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException('Cannot compile reference definition.', previous: $e);
        }

        if (!in_array($containerIdentifier, [ContainerInterface::class, DiContainerInterface::class, DiContainer::class], true)) {
            $this->diContainerDefinitions->pushToDefinitionIterator($containerIdentifier);
        }

        $expression = sprintf('%s->get(%s)', $containerVariableName, var_export($containerIdentifier, true));

        return new CompiledEntry($expression, false);
    }

    public function getDiDefinition(): DiDefinitionLinkInterface
    {
        return $this->definition;
    }
}
