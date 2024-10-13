<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

class ClassWithMethodWithDependency
{
    public function __construct(
        private NameService $service1
    ) {}

    public function sayHello(
        GreetingService $greetingService,
        string $icon
    ): string {
        return \sprintf(
            '%s %s %s',
            $greetingService->getGreeting(),
            $this->service1->name,
            $icon
        );
    }
}
