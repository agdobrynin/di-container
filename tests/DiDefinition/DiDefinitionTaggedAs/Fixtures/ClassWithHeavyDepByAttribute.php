<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassWithHeavyDepByAttribute
{
    /**
     * @param iterable<int, Closure():HeavyDepInterface> $collectionHeavyDep
     */
    public function __construct(
        #[TaggedAs('tags.heavy.dep')]
        private iterable $collectionHeavyDep
    ) {}

    /**
     * @return iterable<int, Closure():HeavyDepInterface>
     */
    public function getDep(): iterable
    {
        yield from $this->collectionHeavyDep;
    }
}
