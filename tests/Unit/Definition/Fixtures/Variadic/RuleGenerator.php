<?php

declare(strict_types=1);

namespace Tests\Unit\Definition\Fixtures\Variadic;

class RuleGenerator
{
    /**
     * @var RuleInterface[]
     */
    protected array $parameters;

    public function __construct(RuleInterface ...$inputRule)
    {
        $this->parameters = $inputRule;
    }

    public function getRules(): ?array
    {
        return $this->parameters ?? null;
    }
}
