<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures\Variadic;

class ParameterIterableVariadic
{
    private array $parameters;

    public function __construct(iterable ...$parameter)
    {
        $this->parameters = $parameter;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
