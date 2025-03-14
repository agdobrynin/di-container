<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\ImportAutoconfigure\Fixtures\Factories;

use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;
use Tests\DefinitionsLoader\ImportAutoconfigure\Fixtures\Person;

#[AutowireExclude]
final class DiFactoryPerson implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Person
    {
        return new Person('Ivan', 'Petrov', 22);
    }
}
