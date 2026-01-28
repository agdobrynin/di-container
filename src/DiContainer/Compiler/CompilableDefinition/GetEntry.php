<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Throwable;

use function array_keys;
use function implode;
use function rtrim;
use function sprintf;
use function var_export;

final class GetEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionLinkInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $containerIdentifier = $this->definition->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw $this->exceptionCompile(previous: $e);
        }

        if ($this->diContainerDefinitions->isContainerIdentifierExcluded($containerIdentifier)) {
            return new CompiledEntry(
                isSingleton: false,
                expression: sprintf('%s->get(%s)', $containerVar, var_export($containerIdentifier, true)),
            );
        }

        $this->diContainerDefinitions->pushToDefinitionIterator($containerIdentifier);
        $circularChecker = [];
        $circularChecker[$containerIdentifier] = true;

        do {
            $definition = $this->diContainerDefinitions->getDefinition($containerIdentifier);
            if ($definition instanceof DiDefinitionLinkInterface) {
                try {
                    $containerIdentifier = $definition->getDefinition();
                } catch (DiDefinitionExceptionInterface $e) {
                    throw $this->exceptionCompile(
                        sprintf('Get reference from "%s".', $containerIdentifier),
                        $e
                    );
                }
                if (isset($circularChecker[$containerIdentifier])) {
                    $ids = [...array_keys($circularChecker), $containerIdentifier];

                    throw new DefinitionCompileException(
                        sprintf('Detected circular call reference for container identifiers "%s"', implode('" -> "', $ids))
                    );
                }

                $circularChecker[$containerIdentifier] = true;
                $this->diContainerDefinitions->pushToDefinitionIterator($containerIdentifier);
            }
        } while ($definition instanceof DiDefinitionLinkInterface);

        return new CompiledEntry(
            isSingleton: false,
            expression: sprintf('%s->get(%s)', $containerVar, var_export($containerIdentifier, true)),
        );
    }

    public function getDiDefinition(): DiDefinitionLinkInterface
    {
        return $this->definition;
    }

    private function exceptionCompile(string $message = '', ?Throwable $previous = null): DefinitionCompileException
    {
        return new DefinitionCompileException(
            rtrim('Cannot compile reference definition. '.$message, ' '),
            previous: $previous,
        );
    }
}
