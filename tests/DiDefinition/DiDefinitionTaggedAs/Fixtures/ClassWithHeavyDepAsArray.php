<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

class ClassWithHeavyDepAsArray
{
    /**
     * @param array<int, Closure():HeavyDepInterface> $collectionHeavyDep
     */
    public function __construct(private array $collectionHeavyDep) {}

    /**
     * @return array<int, Closure():HeavyDepInterface>
     */
    public function getDep(): array
    {
        return $this->collectionHeavyDep;
    }
}
