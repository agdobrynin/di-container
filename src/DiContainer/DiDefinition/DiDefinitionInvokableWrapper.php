<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;

final class DiDefinitionInvokableWrapper implements DiDefinitionInvokableInterface
{
    use DiContainerTrait;

    public function __construct(private DiDefinitionInvokableInterface $definition, private ?bool $isSingleton = null) {}

    public function getDefinition(): DiDefinitionInvokableInterface
    {
        return $this->definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): mixed
    {
        return $this->definition
            ->setContainer($this->getContainer())
            ->invoke()
        ;
    }
}
