<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic;

class TaggedVariadicParameters
{
    public iterable $variadic;

    /**
     * @param iterable<OneInterface[]|TwoInterface[]> ...$variadic
     */
    public function __construct(iterable ...$variadic)
    {
        $this->variadic = $variadic;
    }
}
