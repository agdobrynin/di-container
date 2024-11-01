<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class VariadicSimpleArgumentsByInject
{
    public array $sayHello;

    public function __construct(
        #[Inject('@messages.welcome')]
        string ...$word
    ) {
        $this->sayHello = $word;
    }

    public function say(
        #[Inject('@messages.icon')]
        string ...$icon
    ): string {
        return \implode('_', $this->sayHello).\implode(' ', $icon);
    }

    public static function sayStatic(
        #[Inject('@messages.icon')]
        string ...$icon
    ): string {
        return \implode('~', $icon);
    }
}
