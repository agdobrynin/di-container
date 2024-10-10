<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassWithMethodWithDependency
{
    public function __construct(
        // If php-definition in container empty - get by Inject attribute
        #[Inject(arguments: ['name' => 'Piter'])]
        private NameService $service1
    ) {}

    public function sayHello(
        // If php-definition in container empty - get by Inject attribute
        #[Inject(arguments: ['greeting' => 'Aloha'])]
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
