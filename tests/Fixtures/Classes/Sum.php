<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Classes;

class Sum implements \Stringable, Classes\Interfaces\SumInterface
{
    public function __construct(protected int $init = 0) {}

    public function __toString(): string
    {
        return 'Init data '.$this->init;
    }

    public function add(int $num): int
    {
        return $this->init + $num;
    }
}
