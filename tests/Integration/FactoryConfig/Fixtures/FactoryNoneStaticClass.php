<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class FactoryNoneStaticClass
{
    public function __construct(private readonly Bar $bar) {}

    public function create(): Foo
    {
        return new Foo($this->bar->str);
        // дополнительное конфигурирование `$foo`
    }
}
