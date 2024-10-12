<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassWithMethodWithDependencyByAttribute
{
    public function __construct(
        #[Inject(arguments: ['name' => 'Piter'])]
        private NameService $service1
    ) {}

    public function sayHello(
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
