<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassWithHeavyDepByAttributeAsArray
{
    /**
     * @param HeavyDepInterface[] $collectionHeavyDep
     */
    public function __construct(
        #[TaggedAs('tags.heavy-dep', false)]
        private array $collectionHeavyDep
    ) {}

    /**
     * @return HeavyDepInterface[]
     */
    public function getDep(): array
    {
        return $this->collectionHeavyDep;
    }
}
