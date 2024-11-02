<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class VariadicSimpleArrayArguments
{
    public array $tokens;

    public function __construct(array ...$token)
    {
        $this->tokens = $token;
    }
}
