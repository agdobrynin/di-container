<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassDepByAttributeWithInterfaceImplement
{
    /**
     * @param \Generator<HeavyDepInterface> $collection
     */
    public function __construct(
        #[TaggedAs(HeavyDepInterface::class)]
        private iterable $collection
    ) {}

    /**
     * @return \Generator<HeavyDepInterface>
     */
    public function getDep(): \Generator
    {
        yield from $this->collection;
    }
}
