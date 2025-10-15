<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;

interface DiSetupAttributeInterface extends DiAttributeInterface
{
    public function isImmutable(): bool;

    /**
     * @return (DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed)[]
     */
    public function getArguments(): array;

    /**
     * @param non-empty-string $method
     */
    public function setMethod(string $method): void;
}
