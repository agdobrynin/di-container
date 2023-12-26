<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Classes\Interfaces\SumInterface;

class MethodWithDependencies
{
    public function __construct(public EasyContainer $container) {}

    public function view(int $value, SumInterface $sum): int
    {
        return $sum->add($value);
    }
}
