<?php

declare(strict_types=1);

namespace Tests\FromDocs\Call\Fixtires;

class ServiceOne
{
    public function save(string $name): void
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Argument name cannot be empty.');
        }
        // do save data...
    }
}
