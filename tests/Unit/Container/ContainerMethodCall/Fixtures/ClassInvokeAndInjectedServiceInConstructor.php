<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class ClassInvokeAndInjectedServiceInConstructor
{
    public function __construct(private NameService $service) {}

    public function __invoke(string $greeting): string
    {
        return $greeting.' '.$this->service->name.' 🕶';
    }
}
