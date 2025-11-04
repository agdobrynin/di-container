<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

/**
 * @phpstan-import-type DiDefinitionArgumentType from DiDefinitionArgumentsInterface
 */
interface DiSetupAttributeInterface extends DiAttributeInterface
{
    public function isImmutable(): bool;

    /**
     * @return (DiDefinitionArgumentType|mixed)[]
     */
    public function getArguments(): array;

    /**
     * @param non-empty-string $method
     */
    public function setMethod(string $method): void;

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string;
}
