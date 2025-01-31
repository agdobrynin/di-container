<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Kaspi\DiContainer\Traits\UseAttributeTrait;

final class DiDefinitionProxyClosure implements DiDefinitionInvokableInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use DiContainerTrait;
    use UseAttributeTrait;
    use TagsTrait;

    private string $verifyContainerIdentifier;

    /**
     * @param non-empty-string $containerIdentifier
     */
    public function __construct(private string $containerIdentifier, private ?bool $isSingleton = null) {}

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
        return $this->verifyContainerIdentifier ??= '' === \trim($this->containerIdentifier)
            ? throw new AutowireException(\sprintf('Definition for %s must be non-empty string.', __CLASS__))
            : $this->containerIdentifier;
    }
}
