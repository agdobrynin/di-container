<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class ClassWithParameterTypeAsObject
{
    public function __construct(public object $asObject) {}
}
