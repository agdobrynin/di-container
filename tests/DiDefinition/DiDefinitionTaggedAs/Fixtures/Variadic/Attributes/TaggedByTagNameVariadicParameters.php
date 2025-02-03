<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class TaggedByTagNameVariadicParameters
{
    public iterable $variadic;

    /**
     * @param iterable<OneInterface[]|TwoInterface[]> ...$variadic
     */
    public function __construct(
        #[TaggedAs('tags.tow')]
        #[TaggedAs('tags.one')]
        iterable ...$variadic
    ) {
        $this->variadic = $variadic;
    }
}
