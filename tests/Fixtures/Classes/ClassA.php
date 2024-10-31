<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class ClassA
{
    public function __construct(public A|array|ClassB $var) {}
}
