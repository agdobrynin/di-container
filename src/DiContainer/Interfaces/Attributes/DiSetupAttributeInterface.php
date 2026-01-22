<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type SetupConfigureArgumentsType from SetupConfigureTrait
 */
interface DiSetupAttributeInterface extends DiAttributeInterface
{
    /**
     * @return SetupConfigureArgumentsType
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
