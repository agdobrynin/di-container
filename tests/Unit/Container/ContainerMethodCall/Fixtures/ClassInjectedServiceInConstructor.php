<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class ClassInjectedServiceInConstructor
{
    public function __construct(private SimpleService $service) {}

    public function sayHello(string $greeting): string
    {
        return $greeting.' '.$this->service->name;
    }
}
