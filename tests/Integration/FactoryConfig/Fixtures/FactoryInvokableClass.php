<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class FactoryInvokableClass
{
    public function __invoke(): Foo
    {
        return new Foo('I am from invokable class');
        // дополнительное конфигурирование `$foo`
    }
}
