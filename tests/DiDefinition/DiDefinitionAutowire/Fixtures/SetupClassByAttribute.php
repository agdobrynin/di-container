<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;

class SetupClassByAttribute
{
    private int $inc;
    private array $parameters = [];

    public function getParameters(): array
    {
        return $this->parameters;
    }

    #[Setup(paramName: 'abc', parameters: ['one', 'two', 'three'])]
    #[Setup(paramName: 'path', parameters: ['/tmp', '/var/cache'])]
    public function setParameters(string $paramName, array $parameters): void
    {
        $this->parameters[$paramName] = $parameters;
    }

    #[Setup] // 1
    #[Setup] // 2
    #[Setup] // 3
    #[Setup] // 4
    public function incInc(): void
    {
        isset($this->inc) ? $this->inc++ : $this->inc = 1;
    }

    public function getInc(): int
    {
        return $this->inc;
    }
}
