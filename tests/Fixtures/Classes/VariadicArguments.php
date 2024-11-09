<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class VariadicArguments
{
    /**
     * @var VariadicParameterRule[]
     */
    protected array $parameters;

    public function __construct(?VariadicParameterRule ...$rule)
    {
        $this->parameters = $rule;
    }

    public function getRules(): ?array
    {
        return $this->parameters[0] ? $this->parameters : null;
    }
}
