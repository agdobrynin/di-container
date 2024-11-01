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
}
