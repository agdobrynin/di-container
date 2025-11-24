<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

use function sprintf;
use function trim;

final class DiDefinitionGet implements DiDefinitionLinkInterface, DiDefinitionNoArgumentsInterface
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
        return $this->validContainerIdentifier ??= '' === trim($this->containerIdentifier)
            ? throw new DiDefinitionException(sprintf('Parameter $containerIdentifier for %s::__construct() must be non-empty string.', self::class))
            : $this->containerIdentifier;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        return $container->get($this->getDefinition());
    }
}
