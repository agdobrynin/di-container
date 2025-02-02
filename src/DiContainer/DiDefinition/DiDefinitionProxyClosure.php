<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;

final class DiDefinitionProxyClosure implements DiDefinitionInvokableInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use DiContainerTrait;
    use TagsTrait;

    private string $verifyDefinition;

    /**
     * @param non-empty-string $definition
     */
    public function __construct(private string $definition, private ?bool $isSingleton = null) {}

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): \Closure
    {
        if (!$this->getContainer()->has($this->getDefinition())) {
            throw new AutowireException(\sprintf('Definition "%s" does not exist', $this->getDefinition()));
        }

        return function () { // @phan-suppress-current-line PhanUnreferencedClosure
            return $this->container->get($this->getDefinition());
        };
    }

    public function getDefinition(): string
    {
        return $this->verifyDefinition ??= '' === \trim($this->definition)
            ? throw new AutowireException(\sprintf('Definition for %s must be non-empty string.', __CLASS__))
            : $this->definition;
    }
}
