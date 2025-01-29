<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

class ClassWithHeavyDep
{
    /**
     * @param iterable<int, \Closure(): HeavyDepInterface> $collectionHeavyDep
     */
    public function __construct(private iterable $collectionHeavyDep) {}

    /**
     * @return iterable<int, \Closure(): HeavyDepInterface>
     */
    public function getDep(): iterable
    {
        yield from $this->collectionHeavyDep;
    }
}
