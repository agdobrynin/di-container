<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;

use function sprintf;
use function trim;

final class DiDefinitionProxyClosure implements DiDefinitionSingletonInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use TagsTrait;

    /**
     * @var non-empty-string
     */
    private string $verifyDefinition;

    /**
     * @param non-empty-string $definition
     */
    public function __construct(private readonly string $definition, private readonly ?bool $isSingleton = null) {}

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): Closure
    {
        if (!$container->has($this->getDefinition())) {
            throw new DiDefinitionException(sprintf('Cannot get entry by container identifier "%s"', $this->getDefinition()));
        }

        return function () use ($container) {
            return $container->get($this->getDefinition());
        };
    }

    /**
     * @return non-empty-string
     */
    public function getDefinition(): string
    {
        if (isset($this->verifyDefinition)) {
            return $this->verifyDefinition;
        }

        if ('' === trim($this->definition)) {
            throw new DiDefinitionException(sprintf('Parameter $definition for %s::__construct() must be non-empty string.', __CLASS__));
        }

        return $this->verifyDefinition = $this->definition;
    }
}
