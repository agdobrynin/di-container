<?php

declare(strict_types=1);

namespace Tests\ObjectResetters\Fixtures;

use Kaspi\DiContainer\Interfaces\ResetInterface;

final class Bar implements ResetInterface
{
    public function __construct(private ?string $name) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function reset(): void
    {
        $this->name = null;
    }
}
