<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

class SetupClass
{
    private ?string $name = null;
    private ?string $previousName = null;
    private array $parameters = [];
    private int $inc;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $newName): void
    {
        $this->previousName = $this->name;
        $this->name = $newName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(string $paramName, array $parameters): void
    {
        $this->parameters[$paramName] = $parameters;
    }

    public function getPreviousName(): ?string
    {
        return $this->previousName;
    }

    public function incInc(): void
    {
        isset($this->inc) ? $this->inc++ : $this->inc = 1;
    }

    public function getInc(): int
    {
        return $this->inc;
    }
}
