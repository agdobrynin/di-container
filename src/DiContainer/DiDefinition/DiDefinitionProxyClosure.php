<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;

use function sprintf;

final class DiDefinitionProxyClosure implements DiDefinitionProxyClosureInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
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
        if (!$container->has($this->getDefinition())) {
            throw new DiDefinitionException(sprintf('Cannot get entry by container identifier "%s"', $this->getDefinition()));
        }

        $identifier = $this->getDefinition();

        return static fn () => $container->get($identifier);
    }

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
}
