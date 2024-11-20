<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassWithMethodWithDependencyByAttribute
{
    public function __construct(
        #[InjectContext(arguments: ['name' => 'Piter'])]
        private NameService $service1
    ) {}

    public function sayHello(
        #[InjectContext(arguments: ['greeting' => 'Aloha'])]
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
