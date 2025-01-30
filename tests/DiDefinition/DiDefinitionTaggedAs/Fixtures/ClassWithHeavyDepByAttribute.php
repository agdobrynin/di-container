<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

/**
 * @template T of Closure():HeavyDepInterface
 */
class ClassWithHeavyDepByAttribute
{
    /**
     * @param \Generator<T> $collectionHeavyDep
     */
    public function __construct(
        #[TaggedAs('tags.heavy.dep')]
        private iterable $collectionHeavyDep
    ) {}

    /**
     * @return \Generator<T>
     */
    public function getDep(): \Generator
    {
        yield from $this->collectionHeavyDep;
    }
}
