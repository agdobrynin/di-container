<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class TaggedByInterfaceNameVariadicParameters
{
    public iterable $variadic;

    /**
     * @param iterable<OneInterface[]|TwoInterface[]> ...$variadic
     */
    public function __construct(
        #[TaggedAs(TwoInterface::class)]
        #[TaggedAs(OneInterface::class)]
        iterable ...$variadic
    ) {
        $this->variadic = $variadic;
    }
}
