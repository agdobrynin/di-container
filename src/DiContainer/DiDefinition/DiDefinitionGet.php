<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

use function sprintf;

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
}
