<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Kaspi\DiContainer\Traits\UseAttributeTrait;

final class DiDefinitionProxyClosure implements DiDefinitionInvokableInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use PsrContainerTrait;
    use UseAttributeTrait;
    use TagsTrait;

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
            throw new AutowireException("Definition \"{$this->definition}\" does not exist");
        }

        return function () { // @phan-suppress-current-line PhanUnreferencedClosure
            return $this->container->get($this->getDefinition());
        };
    }

    public function getDefinition(): string
    {
        if ('' !== \trim($this->definition)) {
            return $this->definition;
        }

        throw new AutowireException('Definition for '.__CLASS__.' must be non-empty string.');
    }
}
