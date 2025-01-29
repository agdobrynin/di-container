<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

/**
 * @template T of Closure():HeavyDepInterface
 */
class ClassWithHeavyDep
{
    /**
     * @param iterable<int, T> $collectionHeavyDep
     */
    public function __construct(private iterable $collectionHeavyDep) {}

    /**
     * @return \Generator<T>
     */
    public function getDep(): \Generator
    {
        yield from $this->collectionHeavyDep;
    }
}
