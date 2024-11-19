<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByReference;

class VariadicSimpleArgumentsByInject
{
    public array $sayHello;

    public function __construct(
        #[InjectByReference('messages.welcome')]
        string ...$word
    ) {
        $this->sayHello = $word;
    }

    public function say(
        #[InjectByReference('messages.icon')]
        string ...$icon
    ): string {
        return \implode('_', $this->sayHello).' | '.\implode(' ', $icon);
    }

    public static function sayStatic(
        #[InjectByReference('messages.icon')]
        string ...$icon
    ): string {
        return \implode('~', $icon);
    }

    public static function injectStringDirect(
        #[Inject('hello')]
        #[Inject('world')]
        #[Inject('!')]
        string ...$word
    ): \Generator
    {
        foreach ($word as $w) {
            yield $w;
        }
    }
}
