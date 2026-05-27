<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

use function is_callable;

final class SourceDefinitionItem
{
    /** @var class-string|non-empty-string */
    public readonly string $containerIdentifier;
    public readonly DiDefinitionInterface $diDefinition;

    /**
     * @throws ContainerIdentifierExceptionInterface
     */
    public function __construct(
        int|string $sourceContainerIdentifier,
        mixed $definitionValue,
        public readonly bool $isMutable,
        public bool $isReplaceRemovedId = false,
    ) {
        $this->containerIdentifier = Helper::getContainerIdentifier($sourceContainerIdentifier, $definitionValue);

        $this->diDefinition = match (true) {
            $definitionValue instanceof DiDefinitionInterface => $definitionValue,
            is_callable($definitionValue) => new DiDefinitionCallable($definitionValue),
            default => new DiDefinitionValue($definitionValue)
        };
    }
}
