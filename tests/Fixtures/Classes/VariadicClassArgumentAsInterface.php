<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Classes\Interfaces\VariadicParameterInterface;

class VariadicClassArgumentAsInterface
{
    /**
     * @var VariadicParameterInterface[]
     */
    protected array $parameters;

    public function __construct(VariadicParameterInterface ...$parameter)
    {
        $this->parameters = $parameter;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}