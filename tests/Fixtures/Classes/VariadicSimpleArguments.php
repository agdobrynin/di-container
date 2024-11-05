<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class VariadicSimpleArguments
{
    public array $sayHello;

    public function __construct(string ...$word)
    {
        $this->sayHello = $word;
    }

    public static function say(string ...$word): string
    {
        return \implode('_', $word);
    }

    public static function sayStatic(string ...$word): string
    {
        return \implode('_', $word);
    }
}
