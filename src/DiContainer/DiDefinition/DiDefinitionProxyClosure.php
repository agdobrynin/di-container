<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;

use function sprintf;
use function var_export;

final class DiDefinitionProxyClosure implements DiDefinitionSingletonInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface, DiDefinitionCompileInterface
{
    use TagsTrait;

    /**
     * @var non-empty-string
     */
    private string $validContainerIdentifier;

    /**
     * @param non-empty-string $containerIdentifier
     */
    public function __construct(private readonly string $containerIdentifier, private readonly ?bool $isSingleton = null) {}

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): Closure
    {
        $identifier = $this->getValidContainerIdentifier($container);

        return static fn () => $container->get($identifier);
    }

    public function compile(string $containerVariableName, DiContainerInterface $container, ?string $scopeServiceVariableName = null, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $identifier = $this->getValidContainerIdentifier($container);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionCompileException(
                sprintf('Cannot compile definition as Proxy Closure via identifier "%s".', $this->containerIdentifier),
                previous: $e
            );
        }

        $expression = sprintf('fn () => %s->get(%s)', $containerVariableName, var_export($identifier, true));
        $isSingleton = $this->isSingleton() ?? $container->getConfig()->isSingletonServiceDefault();

        return new CompiledEntry($expression, '', '', [], $isSingleton, '\Closure');
    }

    /**
     * @return non-empty-string
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

    /**
     * @throws DiDefinitionExceptionInterface
     */
    private function getValidContainerIdentifier(DiContainerInterface $container): string
    {
        if (!$container->has($this->getDefinition())) {
            throw new DiDefinitionException(sprintf('Cannot get entry by container identifier "%s"', $this->getDefinition()));
        }

        return $this->getDefinition();
    }
}
