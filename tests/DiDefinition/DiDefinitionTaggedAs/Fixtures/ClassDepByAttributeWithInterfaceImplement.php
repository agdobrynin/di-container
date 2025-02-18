<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassDepByAttributeWithInterfaceImplement
{
    /**
     * @param iterable<HeavyDepInterface> $collection
     */
    public function __construct(
        #[TaggedAs(HeavyDepInterface::class)]
        private iterable $collection
    ) {}

    /**
     * @return iterable<HeavyDepInterface>
     */
    public function getDep(): iterable
    {
        return $this->collection;
    }
}
