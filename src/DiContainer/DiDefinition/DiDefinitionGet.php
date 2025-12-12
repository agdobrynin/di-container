<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;
use function var_export;

final class DiDefinitionGet implements DiDefinitionLinkInterface, DiDefinitionNoArgumentsInterface, DiDefinitionCompileInterface
{
    /**
     * @var non-empty-string
     */
    private string $validContainerIdentifier;

    /**
     * @param non-empty-string $containerIdentifier
     */
    public function __construct(private readonly string $containerIdentifier) {}

    /**
     * @return non-empty-string
     *
     * @throws DiDefinitionException
     */
    public function getDefinition(): string
    {
        if (isset($this->validContainerIdentifier)) {
            return $this->validContainerIdentifier;
        }

        try {
            return $this->validContainerIdentifier = Helper::getContainerIdentifier($this->containerIdentifier, null);
        } catch (ContainerIdentifierExceptionInterface $e) {
            throw new DiDefinitionException(
                sprintf('Parameter $containerIdentifier for %s::__construct() must be non-empty string.', self::class),
                previous: $e
            );
        }
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        return $container->get($this->getDefinition());
    }

    public function compile(string $containerVariableName, DiContainerInterface $container, ?string $scopeServiceVariableName = null, array $scopeVariableNames = []): CompiledEntryInterface
    {
        try {
            $containerIdentifier = $this->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionCompileException(
                sprintf('Cannot compile reference definition with container identifier "%s".', $this->containerIdentifier),
                previous: $e
            );
        }

        $expression = sprintf('%s->get(%s)', $containerVariableName, var_export($containerIdentifier, true));

        return new CompiledEntry($expression, '', [], false);
    }
}
