<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportAutoconfigure\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;
use Tests\DefinitionsLoader\ImportAutoconfigure\Fixtures\Factories\DiFactoryPerson;

#[DiFactory(DiFactoryPerson::class)]
final class Person
{
    public function __construct(
        public string $name,
        public string $surname,
        public int $age,
    ) {}
}
