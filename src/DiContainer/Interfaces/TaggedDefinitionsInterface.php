<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;

interface TaggedDefinitionsInterface
{
    /**
     * Find all definitions by tag.
     *
     * @param non-empty-string $tag
     *
     * @return iterable<non-empty-string, (DiDefinitionAutowireInterface&DiTaggedDefinitionInterface)|DiTaggedDefinitionInterface>
     */
    public function findTaggedDefinitions(string $tag): iterable;
}
