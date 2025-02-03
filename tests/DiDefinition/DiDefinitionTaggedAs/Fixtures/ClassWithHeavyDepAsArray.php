<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

/**
 * @template T of Closure():HeavyDepInterface
 */
class ClassWithHeavyDepAsArray
{
    /**
     * @param array<int, T> $collectionHeavyDep
     */
    public function __construct(private array $collectionHeavyDep) {}

    /**
     * @return array<int, T>
     */
    public function getDep(): array
    {
        return $this->collectionHeavyDep;
    }
}
